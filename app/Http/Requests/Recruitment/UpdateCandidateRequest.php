<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateRequest extends FormRequest
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
            'full_name' => ['sometimes', 'string', 'max:191'],
            'email' => ['sometimes', 'nullable', 'email', 'max:191'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'source' => ['sometimes', 'nullable', 'string', 'max:64'],
            'resume_document_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
