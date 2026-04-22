<?php

namespace Database\Seeders;

use App\Enums\FlightStatus;
use App\Models\Flight;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Flight::query()->create([
            'flight_number' => 'KW101',
            'origin' => 'BER',
            'destination' => 'MAD',
            'departure_at' => now()->addDay()->setTime(9, 15),
            'departed_at' => null,
            'status' => FlightStatus::Scheduled,
        ]);

        Flight::query()->create([
            'flight_number' => 'MO202',
            'origin' => 'LWO',
            'destination' => 'IST',
            'departure_at' => now()->addDays(2)->setTime(14, 40),
            'departed_at' => null,
            'status' => FlightStatus::Boarding,
        ]);

        Flight::factory()->count(3)->create();
    }
}
