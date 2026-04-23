<?php

namespace App\Models;

use App\Enums\FlightStatus;
use Database\Factories\FlightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flight extends Model
{
    /** @use HasFactory<FlightFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'flight_number',
        'origin',
        'destination',
        'gate',
        'departure_at',
        'departed_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'departure_at' => 'datetime',
            'departed_at' => 'datetime',
            'status' => FlightStatus::class,
        ];
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
