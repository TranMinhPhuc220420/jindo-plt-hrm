<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:191'],
            'category' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'in:available,assigned,maintenance,retired,lost'],
            'serial_number' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
