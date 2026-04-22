<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePassengerRequest;
use App\Http\Resources\PassengerResource;
use App\Models\Passenger;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PassengerController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePassengerRequest $request): JsonResponse
    {
        $passenger = Passenger::query()->create($request->validated());

        return (new PassengerResource($passenger))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Passenger $passenger): PassengerResource
    {
        return new PassengerResource($passenger);
    }
}
