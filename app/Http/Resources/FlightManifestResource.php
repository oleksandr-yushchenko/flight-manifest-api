<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightManifestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'flight' => [
                'id' => $this->id,
                'flight_number' => $this->flight_number,
                'origin' => $this->origin,
                'destination' => $this->destination,
                'departure_at' => $this->departure_at?->toIso8601String(),
                'departed_at' => $this->departed_at?->toIso8601String(),
                'status' => $this->status->value,
            ],
            'reservations' => ManifestReservationResource::collection($this->reservations),
        ];
    }
}
