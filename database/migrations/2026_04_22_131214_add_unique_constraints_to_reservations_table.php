<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE UNIQUE INDEX reservations_active_seat_unique
                ON reservations (flight_id, seat_number)
                WHERE status <> 'cancelled'"
            );

            DB::statement(
                "CREATE UNIQUE INDEX reservations_active_passenger_unique
                ON reservations (flight_id, passenger_id)
                WHERE status <> 'cancelled'"
            );

            return;
        }

        DB::statement(
            "CREATE UNIQUE INDEX reservations_active_seat_unique
            ON reservations (flight_id, seat_number)
            WHERE status <> 'cancelled'"
        );

        DB::statement(
            "CREATE UNIQUE INDEX reservations_active_passenger_unique
            ON reservations (flight_id, passenger_id)
            WHERE status <> 'cancelled'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS reservations_active_seat_unique');
        DB::statement('DROP INDEX IF EXISTS reservations_active_passenger_unique');
    }
};
