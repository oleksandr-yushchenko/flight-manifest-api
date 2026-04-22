<?php

namespace App\Http\Requests;

use App\Enums\FlightStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlightRequest extends FormRequest
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
        return [
            'flight_number' => ['required', 'string', 'max:255', 'unique:flights,flight_number'],
            'origin' => ['required', 'string', 'size:3'],
            'destination' => ['required', 'string', 'size:3'],
            'departure_at' => ['required', 'date', 'after:now'],
            'status' => ['required', Rule::enum(FlightStatus::class)],
            'departed_at' => ['nullable', 'date'],
        ];
    }
}
