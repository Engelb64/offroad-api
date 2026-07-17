<?php

namespace App\Http\Requests\Workshop;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('media.max_upload_kb', 5120);

        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.$maxKb,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Debes enviar el archivo en el campo photo.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.mimes' => 'Formatos permitidos: jpg, png, webp.',
            'photo.max' => 'La imagen no puede superar '.((int) config('media.max_upload_kb', 5120)).' KB.',
        ];
    }
}
