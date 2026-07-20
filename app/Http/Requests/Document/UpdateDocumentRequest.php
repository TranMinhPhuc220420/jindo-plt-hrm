<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
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
            'category' => ['sometimes', 'string', 'in:policy,template,contract,certificate,other'],
            'title' => ['sometimes', 'nullable', 'string', 'max:191'],
        ];
    }
}
