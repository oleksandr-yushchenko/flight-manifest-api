<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlightRequest;
use App\Http\Resources\FlightResource;
use App\Models\Flight;
use App\Services\FlightStatusSyncService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FlightController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return FlightResource::collection(
            Flight::query()->latest('id')->get(),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFlightRequest $request): JsonResponse
    {
        $flight = Flight::query()->create($request->validated());

        return (new FlightResource($flight))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Flight $flight): FlightResource
    {
        return new FlightResource($flight);
    }

    /**
     * Syncs the flight status from an external airline service.
     */
    public function syncStatus(Flight $flight, FlightStatusSyncService $flightStatusSyncService): FlightResource|JsonResponse
    {
        try {
            $flight = $flightStatusSyncService->sync($flight);
        } catch (ConnectionException|RequestException) {
            throw ValidationException::withMessages([
                'external_service' => 'Unable to sync flight status from external airline service.',
            ])->status(Response::HTTP_BAD_GATEWAY);
        }

        return new FlightResource($flight);
    }
}
