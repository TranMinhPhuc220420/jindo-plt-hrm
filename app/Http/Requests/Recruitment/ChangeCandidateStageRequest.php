<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class ChangeCandidateStageRequest extends FormRequest
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
            'stage' => ['required', 'string', 'in:applied,screening,interview,offer,hired,rejected,withdrawn'],
        ];
    }
}
