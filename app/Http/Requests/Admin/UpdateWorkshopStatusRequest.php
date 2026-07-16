<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorkshopStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkshopStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(WorkshopStatus::class)],
            'verified' => ['sometimes', 'boolean'],
        ];
    }
}
