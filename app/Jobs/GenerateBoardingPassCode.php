<?php

namespace App\Jobs;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Support\Str;

#[Backoff([5, 10, 20])]
class GenerateBoardingPassCode implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $reservationId)
    {
        $this->onConnection('redis')->onQueue('boarding_pass')->delay(30);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reservation = Reservation::query()
            ->with('flight')
            ->find($this->reservationId);

        if ($reservation === null || $reservation->status !== ReservationStatus::CheckedIn) {
            return;
        }

        $reservation->forceFill([
            'boarding_pass_code' => sprintf(
                '%s-%s-%s',
                $reservation->flight->flight_number,
                $reservation->seat_number,
                Str::upper(Str::random(6)),
            ),
        ])->save();
    }
}
