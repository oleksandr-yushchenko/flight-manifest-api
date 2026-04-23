<?php

use App\Enums\FlightStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

Route::get('mock-airline/flights/{flightNumber}/status', function (Request $request, string $flightNumber) {
    $allowedTransitions = [
        FlightStatus::Scheduled->value => [
            FlightStatus::Delayed->value,
            FlightStatus::Boarding->value,
            FlightStatus::Cancelled->value,
        ],
        FlightStatus::Delayed->value => [
            FlightStatus::Cancelled->value,
            FlightStatus::Boarding->value,
        ],
        FlightStatus::Boarding->value => [
            FlightStatus::Departed->value,
        ],
        FlightStatus::Departed->value => [],
        FlightStatus::Cancelled->value => [],
    ];
    $activeGateStatuses = [
        FlightStatus::Scheduled->value,
        FlightStatus::Delayed->value,
        FlightStatus::Boarding->value,
    ];
    $currentStatus = $request->string('current_status')->toString();
    $requestedStatus = $request->string('status')->toString();
    $currentStatus = array_key_exists($currentStatus, $allowedTransitions)
        ? $currentStatus
        : FlightStatus::Scheduled->value;
    $nextStatuses = $allowedTransitions[$currentStatus];

    $status = $nextStatuses === []
        ? $currentStatus
        : (
            in_array($requestedStatus, $nextStatuses, true)
                ? $requestedStatus
                : (function (array $nextStatuses): string {
                    if (! in_array(FlightStatus::Cancelled->value, $nextStatuses, true)) {
                        return $nextStatuses[array_rand($nextStatuses)];
                    }

                    if (random_int(1, 100) <= 10) {
                        return FlightStatus::Cancelled->value;
                    }

                    $nonCancelledStatuses = array_values(array_filter(
                        $nextStatuses,
                        fn (string $status): bool => $status !== FlightStatus::Cancelled->value,
                    ));

                    return $nonCancelledStatuses[array_rand($nonCancelledStatuses)];
                })($nextStatuses)
        );

    $gate = null;
    if (in_array($status, $activeGateStatuses, true)) {
        $gate = $request->query('gate');

        if ($gate === null || $gate === '') {
            $gate = sprintf(
                '%s%02d',
                chr(65 + (abs(crc32($flightNumber)) % 6)),
                (abs(crc32(strrev($flightNumber))) % 30) + 1,
            );
        }
    }

    $departedAt = $status === FlightStatus::Departed->value
        ? (
            $request->filled('departed_at')
                ? Carbon::parse($request->string('departed_at')->toString())->toIso8601String()
                : now()->toIso8601String()
        )
        : null;

    return response()->json([
        'flight_number' => strtoupper($flightNumber),
        'current_status' => $currentStatus,
        'status' => $status,
        'gate' => $gate,
        'departed_at' => $departedAt,
    ]);
})->name('mock-airline.flights.status');
