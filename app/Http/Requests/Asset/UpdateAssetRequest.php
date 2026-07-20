<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:64'],
            'name' => ['sometimes', 'string', 'max:191'],
            'category' => ['sometimes', 'nullable', 'string', 'max:64'],
            'status' => ['sometimes', 'string', 'in:available,assigned,maintenance,retired,lost'],
            'serial_number' => ['sometimes', 'nullable', 'string', 'max:191'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
