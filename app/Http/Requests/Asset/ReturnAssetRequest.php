<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class ReturnAssetRequest extends FormRequest
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
            'returned_at' => ['nullable', 'date'],
            'condition' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string'],
        ];
    }
}
