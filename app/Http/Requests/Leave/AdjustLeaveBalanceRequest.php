<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class AdjustLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_leave_balances') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer'],
            'leave_type_id' => ['required', 'integer'],
            'period_key' => ['sometimes', 'string', 'max:16'],
            'delta' => ['required', 'numeric'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
