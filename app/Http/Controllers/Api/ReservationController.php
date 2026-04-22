<?php

namespace App\Http\Controllers\Api;

use App\Enums\FlightStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\FlightManifestResource;
use App\Http\Resources\ReservationResource;
use App\Jobs\GenerateBoardingPassCode;
use App\Models\Flight;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
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

        return (new ReservationResource($reservation->load(['flight', 'passenger'])))
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

    public function checkIn(Reservation $reservation): ReservationResource
    {
        $reservation->loadMissing(['flight', 'passenger']);

        if ($reservation->status !== ReservationStatus::Booked) {
            throw ValidationException::withMessages([
                'reservation' => 'Only booked reservations can be checked in.',
            ]);
        }

        if ($reservation->flight->status === FlightStatus::Departed) {
            throw ValidationException::withMessages([
                'flight' => 'Cannot check in to a departed flight.',
            ]);
        }

        $reservation->forceFill([
            'status' => ReservationStatus::CheckedIn,
            'checked_in_at' => now(),
        ])->save();

        GenerateBoardingPassCode::dispatch($reservation->id);

        return new ReservationResource($reservation->refresh()->load(['flight', 'passenger']));
    }

    public function cancel(Reservation $reservation): ReservationResource
    {
        $reservation->loadMissing(['flight', 'passenger']);

        if (! in_array($reservation->status, [ReservationStatus::Booked, ReservationStatus::CheckedIn], true)) {
            throw ValidationException::withMessages([
                'reservation' => 'Only booked or checked-in reservations can be cancelled.',
            ]);
        }

        $reservation->forceFill([
            'status' => ReservationStatus::Cancelled,
            'boarding_pass_code' => null,
        ])->save();

        return new ReservationResource($reservation->refresh()->load(['flight', 'passenger']));
    }
}
