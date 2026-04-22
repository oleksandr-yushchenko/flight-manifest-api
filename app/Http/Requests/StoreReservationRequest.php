<?php

namespace App\Http\Requests;

use App\Enums\FlightStatus;
use App\Enums\ReservationStatus;
use App\Models\Flight;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        /** @var Flight $flight */
        $flight = $this->route('flight');

        return [
            'passenger_id' => [
                'required',
                'integer',
                'exists:passengers,id',
                Rule::unique('reservations', 'passenger_id')->where(
                    fn ($query) => $query
                        ->where('flight_id', $flight->id)
                        ->whereNot('status', ReservationStatus::Cancelled->value),
                ),
            ],
            'seat_number' => [
                'required',
                'string',
                'regex:/^(?:[1-9]|[1-2][0-9]|3[0-6])[A-F]$/',
                Rule::unique('reservations', 'seat_number')->where(
                    fn ($query) => $query
                        ->where('flight_id', $flight->id)
                        ->whereNot('status', ReservationStatus::Cancelled->value),
                ),
            ],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Flight $flight */
                $flight = $this->route('flight');

                if (in_array($flight->status, [FlightStatus::Cancelled, FlightStatus::Departed], true)) {
                    $validator->errors()->add(
                        'flight',
                        'Reservations cannot be created for cancelled or departed flights.',
                    );
                }
            },
        ];
    }
}
