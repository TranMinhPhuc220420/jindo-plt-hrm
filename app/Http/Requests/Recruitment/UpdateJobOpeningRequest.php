<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobOpeningRequest extends FormRequest
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
            'code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'title' => ['sometimes', 'string', 'max:191'],
            'department_id' => ['sometimes', 'nullable', 'integer'],
            'position_id' => ['sometimes', 'nullable', 'integer'],
            'description' => ['sometimes', 'nullable', 'string'],
            'headcount' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
