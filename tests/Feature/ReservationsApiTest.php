<?php

namespace Tests\Feature;

use App\Enums\FlightStatus;
use App\Enums\ReservationStatus;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReservationsApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, int|string>
     */
    private function validPayload(Passenger $passenger): array
    {
        return [
            'passenger_id' => $passenger->id,
            'seat_number' => '12C',
        ];
    }

    public function test_successful_reservation_creation(): void
    {
        $flight = Flight::factory()->create();
        $passenger = Passenger::factory()->create();

        $response = $this->postJson(
            "/api/flights/{$flight->id}/reservations",
            $this->validPayload($passenger),
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', ReservationStatus::Booked->value)
            ->assertJsonPath('data.seat_number', '12C')
            ->assertJsonPath('data.flight.id', $flight->id)
            ->assertJsonPath('data.passenger.id', $passenger->id);

        $this->assertDatabaseHas('reservations', [
            'flight_id' => $flight->id,
            'passenger_id' => $passenger->id,
            'seat_number' => '12C',
            'status' => ReservationStatus::Booked->value,
        ]);
    }

    public function test_duplicate_seat_rejection(): void
    {
        $flight = Flight::factory()->create();
        $firstPassenger = Passenger::factory()->create();
        $secondPassenger = Passenger::factory()->create();

        Reservation::factory()->create([
            'flight_id' => $flight->id,
            'passenger_id' => $firstPassenger->id,
            'seat_number' => '12C',
        ]);

        $response = $this->postJson("/api/flights/{$flight->id}/reservations", [
            'passenger_id' => $secondPassenger->id,
            'seat_number' => '12C',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['seat_number']);
    }

    public function test_cancelled_reservation_does_not_block_same_seat(): void
    {
        $flight = Flight::factory()->create();
        $firstPassenger = Passenger::factory()->create();
        $secondPassenger = Passenger::factory()->create();

        Reservation::factory()->create([
            'flight_id' => $flight->id,
            'passenger_id' => $firstPassenger->id,
            'seat_number' => '12C',
            'status' => ReservationStatus::Cancelled,
        ]);

        $response = $this->postJson("/api/flights/{$flight->id}/reservations", [
            'passenger_id' => $secondPassenger->id,
            'seat_number' => '12C',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.seat_number', '12C')
            ->assertJsonPath('data.passenger.id', $secondPassenger->id);
    }

    public function test_duplicate_passenger_reservation_rejection(): void
    {
        $flight = Flight::factory()->create();
        $passenger = Passenger::factory()->create();

        Reservation::factory()->create([
            'flight_id' => $flight->id,
            'passenger_id' => $passenger->id,
            'seat_number' => '10A',
        ]);

        $response = $this->postJson("/api/flights/{$flight->id}/reservations", [
            'passenger_id' => $passenger->id,
            'seat_number' => '12C',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['passenger_id']);
    }

    public function test_cancelled_reservation_does_not_block_same_passenger(): void
    {
        $flight = Flight::factory()->create();
        $passenger = Passenger::factory()->create();

        Reservation::factory()->create([
            'flight_id' => $flight->id,
            'passenger_id' => $passenger->id,
            'seat_number' => '10A',
            'status' => ReservationStatus::Cancelled,
        ]);

        $response = $this->postJson("/api/flights/{$flight->id}/reservations", [
            'passenger_id' => $passenger->id,
            'seat_number' => '12C',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.passenger.id', $passenger->id)
            ->assertJsonPath('data.seat_number', '12C');
    }

    public function test_reservation_rejected_for_cancelled_flight(): void
    {
        $flight = Flight::factory()->create([
            'status' => FlightStatus::Cancelled,
        ]);
        $passenger = Passenger::factory()->create();

        $response = $this->postJson(
            "/api/flights/{$flight->id}/reservations",
            $this->validPayload($passenger),
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['flight']);
    }

    public function test_reservation_rejected_for_departed_flight(): void
    {
        $flight = Flight::factory()->create([
            'status' => FlightStatus::Departed,
            'departed_at' => now()->subMinute(),
        ]);
        $passenger = Passenger::factory()->create();

        $response = $this->postJson(
            "/api/flights/{$flight->id}/reservations",
            $this->validPayload($passenger),
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['flight']);
    }

    public function test_get_reservation(): void
    {
        $reservation = Reservation::factory()->create();
        $reservation->load(['flight', 'passenger']);

        $response = $this->getJson("/api/reservations/{$reservation->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $reservation->id)
            ->assertJsonPath('data.seat_number', $reservation->seat_number)
            ->assertJsonPath('data.passenger.id', $reservation->passenger->id)
            ->assertJsonPath('data.flight.id', $reservation->flight->id);
    }

    public function test_get_manifest(): void
    {
        $flight = Flight::factory()->create([
            'flight_number' => 'PS555',
        ]);
        $firstPassenger = Passenger::factory()->create([
            'first_name' => 'Anna',
        ]);
        $secondPassenger = Passenger::factory()->create([
            'first_name' => 'Bohdan',
        ]);

        Reservation::factory()->create([
            'flight_id' => $flight->id,
            'passenger_id' => $secondPassenger->id,
            'seat_number' => '14B',
        ]);
        Reservation::factory()->create([
            'flight_id' => $flight->id,
            'passenger_id' => $firstPassenger->id,
            'seat_number' => '12A',
        ]);

        $response = $this->getJson("/api/flights/{$flight->id}/manifest");

        $response
            ->assertOk()
            ->assertJsonPath('data.flight.id', $flight->id)
            ->assertJsonPath('data.flight.flight_number', 'PS555')
            ->assertJsonCount(2, 'data.reservations')
            ->assertJsonPath('data.reservations.0.seat_number', '12A')
            ->assertJsonPath('data.reservations.0.passenger.first_name', 'Anna')
            ->assertJsonPath('data.reservations.1.seat_number', '14B')
            ->assertJsonPath('data.reservations.1.passenger.first_name', 'Bohdan');
    }
}
