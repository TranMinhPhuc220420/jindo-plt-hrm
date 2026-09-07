<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceFlexibleScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_assign_shifts') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'slots' => ['present', 'array', 'max:56'],
            'slots.*.date' => ['required', 'date'],
            'slots.*.start_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'slots.*.end_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ];
    }
}
