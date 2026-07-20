<?php

namespace App\Http\Requests\Leave;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_leave_types') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:150'],
            'unit_default' => ['sometimes', 'string', Rule::in(LeaveType::UNITS)],
            'is_paid' => ['sometimes', 'boolean'],
            'requires_balance' => ['sometimes', 'boolean'],
            'allows_negative' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
