<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seat_number' => $this->seat_number,
            'status' => $this->status->value,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'boarding_pass_code' => $this->boarding_pass_code,
            'flight' => [
                'id' => $this->flight->id,
                'flight_number' => $this->flight->flight_number,
                'status' => $this->flight->status->value,
                'departure_at' => $this->flight->departure_at?->toIso8601String(),
            ],
            'passenger' => [
                'id' => $this->passenger->id,
                'first_name' => $this->passenger->first_name,
                'last_name' => $this->passenger->last_name,
                'email' => $this->passenger->email,
                'document_number' => $this->passenger->document_number,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
