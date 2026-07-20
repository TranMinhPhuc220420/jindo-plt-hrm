<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobOpeningRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:191'],
            'department_id' => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'headcount' => ['nullable', 'integer', 'min:1'],
            'opened_at' => ['nullable', 'date'],
        ];
    }
}
