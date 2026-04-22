<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\FlightManifestResource;
use App\Http\Resources\ReservationResource;
use App\Models\Flight;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request, Flight $flight): JsonResponse
    {
        $reservation = Reservation::query()->create([
            ...$request->validated(),
            'flight_id' => $flight->id,
            'status' => ReservationStatus::Booked,
        ]);

        $reservation->load(['flight', 'passenger']);

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation): ReservationResource
    {
        return new ReservationResource($reservation->load(['flight', 'passenger']));
    }

    /**
     * Display the flight manifest.
     */
    public function manifest(Flight $flight): FlightManifestResource
    {
        return new FlightManifestResource($flight->load([
            'reservations' => fn ($query) => $query
                ->with('passenger')
                ->orderBy('seat_number'),
        ]));
    }
}
