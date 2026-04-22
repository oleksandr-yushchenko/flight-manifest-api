<?php

use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\PassengerController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('health', function (Request $request) {
    return ['status' => 'ok'];
});

Route::apiResource('flights', FlightController::class)->only(['index', 'store', 'show']);
Route::apiResource('passengers', PassengerController::class)->only(['store', 'show']);
Route::post('flights/{flight}/reservations', [ReservationController::class, 'store'])
    ->name('flights.reservations.store');
Route::get('flights/{flight}/manifest', [ReservationController::class, 'manifest'])
    ->name('flights.manifest.show');
Route::get('reservations/{reservation}', [ReservationController::class, 'show'])
    ->name('reservations.show');
Route::post('reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])
    ->name('reservations.check-in');
Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
    ->name('reservations.cancel');
