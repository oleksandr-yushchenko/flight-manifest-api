<?php

namespace App\Enums;

enum FlightStatus: string
{
    case Scheduled = 'scheduled';
    case Boarding = 'boarding';
    case Departed = 'departed';
    case Delayed = 'delayed';
    case Cancelled = 'cancelled';
}
