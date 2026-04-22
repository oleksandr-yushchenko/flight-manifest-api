<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManifestReservationResource extends JsonResource
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
            'passenger' => [
                'id' => $this->passenger->id,
                'first_name' => $this->passenger->first_name,
                'last_name' => $this->passenger->last_name,
                'email' => $this->passenger->email,
                'document_number' => $this->passenger->document_number,
            ],
        ];
    }
}
