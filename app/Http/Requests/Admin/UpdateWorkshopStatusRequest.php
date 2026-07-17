<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorkshopStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'moderation_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== WorkshopStatus::Suspended->value) {
                return;
            }

            $note = trim((string) $this->input('moderation_note', ''));

            if (mb_strlen($note) < 5) {
                $validator->errors()->add(
                    'moderation_note',
                    'El motivo es obligatorio al suspender (mínimo 5 caracteres).',
                );
            }
        });
    }
}
