<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_check_in_out') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'worked_at' => ['sometimes', 'nullable', 'date'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
