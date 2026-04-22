<?php

namespace Database\Seeders;

use App\Models\Passenger;
use Illuminate\Database\Seeder;

class PassengerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Passenger::query()->create([
            'first_name' => 'Olena',
            'last_name' => 'Shevchenko',
            'email' => 'olena.shevchenko@example.test',
            'birthday' => '1992-05-12',
            'document_number' => 'FF123456',
        ]);

        Passenger::query()->create([
            'first_name' => 'Maksym',
            'last_name' => 'Koval',
            'email' => 'maksym.koval@example.test',
            'birthday' => '1988-11-03',
            'document_number' => 'ER654321',
        ]);

        Passenger::factory()->count(8)->create();
    }
}
