<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlightRequest;
use App\Http\Resources\FlightResource;
use App\Models\Flight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
}
