<?php

namespace App\Services;

use App\Enums\FlightStatus;
use App\Models\Flight;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use RuntimeException;

class FlightStatusSyncService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private HttpFactory $http) {}

    /**
     * @throws RequestException
     */
    public function sync(Flight $flight): Flight
    {
        $response = $this->http
            ->baseUrl((string) config('services.airline_status.base_url'))
            ->acceptJson()
            ->timeout((int) config('services.airline_status.timeout'))
            ->get("/flights/{$flight->flight_number}/status", [
                'current_status' => $flight->status->value,
            ]);

        $response->throw();

        $payload = $response->json();
        $status = FlightStatus::tryFrom((string) data_get($payload, 'status'));

        if ($status === null) {
            throw new RuntimeException('External airline service returned an invalid flight status.');
        }

        $departedAt = $this->parseDateTime(data_get($payload, 'departed_at'));

        $flight->forceFill([
            'status' => $status,
            'gate' => $status === FlightStatus::Departed
                ? $flight->gate
                : (data_get($payload, 'gate') ?? $flight->gate),
            'departed_at' => $flight->status === FlightStatus::Departed && $flight->departed_at !== null
                ? $flight->departed_at
                : ($departedAt ?? $flight->departed_at),
        ])->save();

        return $flight->refresh();
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse((string) $value);
    }
}
