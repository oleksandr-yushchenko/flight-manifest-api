<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Booked = 'booked';
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';
}
