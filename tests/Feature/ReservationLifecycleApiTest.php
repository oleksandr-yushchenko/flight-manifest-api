<?php

namespace Tests\Feature;

use App\Enums\FlightStatus;
use App\Enums\ReservationStatus;
use App\Jobs\GenerateBoardingPassCode;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Reservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ReservationLifecycleApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function validCheckInReservation(array $reservationAttributes = [], array $flightAttributes = []): Reservation
    {
        $flight = Flight::factory()->create($flightAttributes);
        $passenger = Passenger::factory()->create();

        return Reservation::factory()->create([
            'flight_id' => $flight->id,
            'passenger_id' => $passenger->id,
            ...$reservationAttributes,
        ]);
    }

    public function test_generate_boarding_pass_job_stores_target_reservation_id(): void
    {
        $reservation = $this->validCheckInReservation();
        $job = new GenerateBoardingPassCode($reservation->id);

        $this->assertSame($reservation->id, $job->reservationId);
    }

    public function test_check_in_dispatches_boarding_pass_job_with_delay(): void
    {
        Bus::fake();

        $reservation = $this->validCheckInReservation();

        $response = $this->postJson("/api/reservations/{$reservation->id}/check-in");

        $response
            ->assertOk()
            ->assertJsonPath('data.status', ReservationStatus::CheckedIn->value)
            ->assertJsonPath('data.boarding_pass_code', null);

        Bus::assertDispatched(GenerateBoardingPassCode::class, function (GenerateBoardingPassCode $job) use ($reservation): bool {
            $this->assertInstanceOf(ShouldQueue::class, $job);

            return $job->reservationId === $reservation->id
                && $job->connection === 'redis'
                && $job->delay === 30;
        });
    }

    public function test_check_in_is_rejected_for_cancelled_reservations(): void
    {
        $reservation = $this->validCheckInReservation([
            'status' => ReservationStatus::Cancelled,
        ]);

        $response = $this->postJson("/api/reservations/{$reservation->id}/check-in");

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reservation']);
    }

    public function test_check_in_is_rejected_when_flight_has_departed(): void
    {
        $reservation = $this->validCheckInReservation(
            reservationAttributes: ['status' => ReservationStatus::Booked],
            flightAttributes: [
                'status' => FlightStatus::Departed,
                'departed_at' => now()->subMinute(),
            ],
        );

        $response = $this->postJson("/api/reservations/{$reservation->id}/check-in");

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['flight']);
    }

    public function test_cancel_moves_booked_reservation_to_cancelled(): void
    {
        $reservation = $this->validCheckInReservation();

        $response = $this->postJson("/api/reservations/{$reservation->id}/cancel");

        $response
            ->assertOk()
            ->assertJsonPath('data.status', ReservationStatus::Cancelled->value)
            ->assertJsonPath('data.boarding_pass_code', null);

        $reservation->refresh();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->status);
    }

    public function test_cancel_moves_checked_in_reservation_to_cancelled_and_clears_boarding_pass(): void
    {
        $reservation = $this->validCheckInReservation([
            'status' => ReservationStatus::CheckedIn,
            'checked_in_at' => now()->subMinute(),
            'boarding_pass_code' => 'PS101-12C-ABC123',
        ]);

        $response = $this->postJson("/api/reservations/{$reservation->id}/cancel");

        $response
            ->assertOk()
            ->assertJsonPath('data.status', ReservationStatus::Cancelled->value)
            ->assertJsonPath('data.boarding_pass_code', null);

        $reservation->refresh();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->status);
        $this->assertNull($reservation->boarding_pass_code);
    }

    public function test_cancel_is_rejected_for_already_cancelled_reservation(): void
    {
        $reservation = $this->validCheckInReservation([
            'status' => ReservationStatus::Cancelled,
        ]);

        $response = $this->postJson("/api/reservations/{$reservation->id}/cancel");

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reservation']);
    }
}
