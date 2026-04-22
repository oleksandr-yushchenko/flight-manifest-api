<?php

namespace Tests\Feature;

use App\Enums\FlightStatus;
use App\Models\Flight;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
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
}
