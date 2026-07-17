<?php

namespace App\Http\Requests\Workshop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexWorkshopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'service' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'sort' => ['nullable', 'string', Rule::in(['recent', 'distance'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('sort') === 'distance' && (! $this->filled('lat') || ! $this->filled('lng'))) {
                $validator->errors()->add(
                    'sort',
                    'sort=distance requiere lat y lng.',
                );
            }
        });
    }
}
