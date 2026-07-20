<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240'],
            'owner_type' => ['required', 'string', 'in:company,employee,candidate'],
            'owner_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'in:policy,template,contract,certificate,other'],
            'title' => ['nullable', 'string', 'max:191'],
        ];
    }
}
