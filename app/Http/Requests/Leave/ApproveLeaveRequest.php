<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_approve_leave') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
