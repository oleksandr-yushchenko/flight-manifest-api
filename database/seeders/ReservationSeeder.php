<?php

namespace Database\Seeders;

use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $flight = Flight::query()->first();

        if ($flight === null) {
            return;
        }

        $passengers = Passenger::query()->take(2)->inRandomOrder()->get();

        foreach ($passengers as $index => $passenger) {
            Reservation::query()->create([
                'flight_id' => $flight->id,
                'passenger_id' => $passenger->id,
                'seat_number' => (string) ($index + 1).'A',
            ]);
        }

        Reservation::factory()->count(5)->create();
    }
}
