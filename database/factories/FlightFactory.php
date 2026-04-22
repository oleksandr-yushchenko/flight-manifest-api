<?php

namespace Database\Factories;

use App\Enums\FlightStatus;
use App\Models\Flight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flight>
 */
class FlightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'flight_number' => strtoupper(fake()->bothify('??###')),
            'origin' => fake()->randomElement(['KBP', 'LWO', 'WAW', 'BER', 'AMS', 'CDG']),
            'destination' => fake()->randomElement(['JFK', 'LHR', 'FRA', 'MAD', 'FCO', 'IST']),
            'departure_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'departed_at' => null,
            'status' => fake()->randomElement([
                FlightStatus::Scheduled,
                FlightStatus::Boarding,
                FlightStatus::Delayed,
            ]),
        ];
    }
}
