<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetDamageReportRequest extends FormRequest
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
            'description' => ['required', 'string'],
            'reported_at' => ['nullable', 'date'],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['integer'],
        ];
    }
}
