<?php

namespace Tests\Feature;

use App\Enums\FlightStatus;
use App\Models\Flight;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlightsApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'flight_number' => 'PS'.now()->addDay()->format('His'),
            'origin' => 'KBP',
            'destination' => 'AMS',
            'departure_at' => now()->addDays(2)->setTime(10, 30)->format('Y-m-d H:i:s'),
            'status' => FlightStatus::Scheduled->value,
        ];
    }

    public function test_create_flight(): void
    {
        $payload = $this->validPayload();
        $response = $this->postJson('/api/flights', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.flight_number', $payload['flight_number'])
            ->assertJsonPath('data.origin', 'KBP')
            ->assertJsonPath('data.destination', 'AMS')
            ->assertJsonPath('data.gate', null)
            ->assertJsonPath('data.status', FlightStatus::Scheduled->value);

        $flight = Flight::query()->firstWhere('flight_number', $payload['flight_number']);

        $this->assertNotNull($flight);
        $this->assertSame('AMS', $flight->destination);
    }

    public function test_list_flights(): void
    {
        $olderFlight = Flight::factory()->create([
            'flight_number' => 'PS100',
        ]);
        $newerFlight = Flight::factory()->create([
            'flight_number' => 'PS200',
        ]);

        $response = $this->getJson('/api/flights');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerFlight->id)
            ->assertJsonPath('data.1.id', $olderFlight->id);
    }

    public function test_get_single_flight(): void
    {
        $flight = Flight::factory()->create([
            'flight_number' => 'PS321',
            'status' => FlightStatus::Delayed,
        ]);

        $response = $this->getJson("/api/flights/{$flight->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $flight->id)
            ->assertJsonPath('data.flight_number', 'PS321')
            ->assertJsonPath('data.gate', null)
            ->assertJsonPath('data.status', FlightStatus::Delayed->value);
    }

    public function test_validation_error_on_invalid_payload(): void
    {
        $response = $this->postJson('/api/flights', [
            'flight_number' => '',
            'origin' => 'KYIV',
            'destination' => '',
            'departure_at' => 'not-a-date',
            'status' => 'invalid-status',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'flight_number',
                'origin',
                'destination',
                'departure_at',
                'status',
            ]);
    }

    public function test_validation_error_when_departure_at_is_in_the_past(): void
    {
        Carbon::setTestNow('2026-04-22 12:00:00');

        $payload = $this->validPayload();
        $payload['departure_at'] = now()->subMinute()->format('Y-m-d H:i:s');

        $response = $this->postJson('/api/flights', $payload);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['departure_at']);

        Carbon::setTestNow();
    }

    public function test_sync_flight_status_from_external_airline_service(): void
    {
        $flight = Flight::factory()->create([
            'flight_number' => 'PS321',
            'status' => FlightStatus::Scheduled,
            'departure_at' => now()->addDay()->setTime(10, 30),
        ]);
        $baseUrl = rtrim((string) config('services.airline_status.base_url'), '/');

        Http::fake([
            "{$baseUrl}/flights/PS321/status*" => Http::response([
                'status' => FlightStatus::Boarding->value,
                'gate' => 'A12',
                'departed_at' => null,
            ]),
        ]);

        $response = $this->postJson("/api/flights/{$flight->id}/sync-status");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $flight->id)
            ->assertJsonPath('data.status', FlightStatus::Boarding->value)
            ->assertJsonPath('data.gate', 'A12');

        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($flight, $baseUrl): bool {
            return $request->url() === "{$baseUrl}/flights/PS321/status?current_status={$flight->status->value}"
                && $request->method() === 'GET';
        });

        $flight->refresh();

        $this->assertSame(FlightStatus::Boarding, $flight->status);
        $this->assertSame('A12', $flight->gate);
    }

    public function test_sync_flight_status_sets_departed_at_for_departed_response(): void
    {
        $flight = Flight::factory()->create([
            'flight_number' => 'PS999',
            'status' => FlightStatus::Boarding,
            'gate' => 'C03',
            'departed_at' => null,
        ]);
        $departedAt = now()->toIso8601String();
        $baseUrl = rtrim((string) config('services.airline_status.base_url'), '/');

        Http::fake([
            "{$baseUrl}/flights/PS999/status*" => Http::response([
                'status' => FlightStatus::Departed->value,
                'gate' => null,
                'departed_at' => $departedAt,
            ]),
        ]);

        $response = $this->postJson("/api/flights/{$flight->id}/sync-status");

        $response
            ->assertOk()
            ->assertJsonPath('data.status', FlightStatus::Departed->value)
            ->assertJsonPath('data.gate', 'C03')
            ->assertJsonPath('data.departed_at', $departedAt);

        $flight->refresh();

        $this->assertSame(FlightStatus::Departed, $flight->status);
        $this->assertSame('C03', $flight->gate);
        $this->assertSame($departedAt, $flight->departed_at?->toIso8601String());
    }

    public function test_sync_does_not_override_existing_gate_or_departed_at_for_departed_flight(): void
    {
        $existingDepartedAt = now()->subHour()->toIso8601String();
        $flight = Flight::factory()->create([
            'flight_number' => 'PS111',
            'status' => FlightStatus::Departed,
            'gate' => 'D14',
            'departed_at' => $existingDepartedAt,
        ]);
        $baseUrl = rtrim((string) config('services.airline_status.base_url'), '/');

        Http::fake([
            "{$baseUrl}/flights/PS111/status*" => Http::response([
                'status' => FlightStatus::Departed->value,
                'gate' => 'A01',
                'departed_at' => now()->toIso8601String(),
            ]),
        ]);

        $response = $this->postJson("/api/flights/{$flight->id}/sync-status");

        $response
            ->assertOk()
            ->assertJsonPath('data.status', FlightStatus::Departed->value)
            ->assertJsonPath('data.gate', 'D14')
            ->assertJsonPath('data.departed_at', $existingDepartedAt);

        $flight->refresh();

        $this->assertSame('D14', $flight->gate);
        $this->assertSame($existingDepartedAt, $flight->departed_at?->toIso8601String());
    }

    public function test_sync_flight_status_returns_bad_gateway_when_external_service_fails(): void
    {
        $flight = Flight::factory()->create([
            'flight_number' => 'PS654',
            'status' => FlightStatus::Scheduled,
        ]);
        $baseUrl = rtrim((string) config('services.airline_status.base_url'), '/');

        Http::fake([
            "{$baseUrl}/flights/PS654/status*" => Http::response([
                'message' => 'upstream unavailable',
            ], 503),
        ]);

        $response = $this->postJson("/api/flights/{$flight->id}/sync-status");

        $response
            ->assertStatus(502)
            ->assertJsonPath('message', 'Unable to sync flight status from external airline service.')
            ->assertJsonPath('errors.external_service.0', 'Unable to sync flight status from external airline service.');

        $flight->refresh();

        $this->assertSame(FlightStatus::Scheduled, $flight->status);
        $this->assertNull($flight->gate);
    }
}
