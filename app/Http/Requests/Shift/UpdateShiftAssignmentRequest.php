<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftAssignmentRequest extends FormRequest
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
            'employee_id' => ['sometimes', 'integer'],
            'shift_id' => ['sometimes', 'integer'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
        ];
    }
}
