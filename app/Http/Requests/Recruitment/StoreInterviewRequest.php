<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewRequest extends FormRequest
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
            'scheduled_at' => ['nullable', 'date'],
            'mode' => ['nullable', 'string', 'max:32'],
            'location' => ['nullable', 'string', 'max:191'],
            'interviewer_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
