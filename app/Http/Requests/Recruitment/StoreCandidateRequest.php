<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateRequest extends FormRequest
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
            'job_opening_id' => ['required', 'integer'],
            'full_name' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:32'],
            'stage' => ['nullable', 'string', 'in:applied,screening,interview,offer,hired,rejected,withdrawn'],
            'source' => ['nullable', 'string', 'max:64'],
        ];
    }
}
