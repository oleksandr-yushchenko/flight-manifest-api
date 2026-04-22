<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $row = fake()->numberBetween(1, 36);
        $seatNumber = $row.fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']);

        return [
            'flight_id' => Flight::factory(),
            'passenger_id' => Passenger::factory(),
            'seat_number' => $seatNumber,
            'status' => ReservationStatus::Booked,
            'checked_in_at' => null,
            'boarding_pass_code' => null,
        ];
    }
}
