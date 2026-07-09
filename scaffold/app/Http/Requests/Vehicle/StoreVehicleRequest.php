<?php

namespace App\Http\Requests\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'vin' => ['nullable', 'string', 'max:32'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'photo_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
