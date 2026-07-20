<?php

namespace App\Http\Requests\Leave;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_request_leave') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer'],
            'employee_id' => ['sometimes', 'integer'],
            'unit' => ['sometimes', 'string', Rule::in(LeaveRequest::UNITS)],
            'start_date' => ['required', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['sometimes', 'boolean'],
            'half_day_period' => ['sometimes', 'nullable', 'string', Rule::in(['am', 'pm'])],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date', 'after:start_at'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
