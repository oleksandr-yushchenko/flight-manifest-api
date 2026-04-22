<?php

use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\PassengerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('health', function (Request $request) {
    return ['status' => 'ok'];
});

Route::apiResource('flights', FlightController::class)->only(['index', 'store', 'show']);
Route::apiResource('passengers', PassengerController::class)->only(['store', 'show']);
