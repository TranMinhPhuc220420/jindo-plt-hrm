<?php

namespace App\Http\Requests\Shift;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_shift_definitions') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'end_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'break_minutes' => ['sometimes', 'integer', 'min:0', 'max:600'],
            'kind' => ['sometimes', 'string', Rule::in(Shift::KINDS)],
            'is_night' => ['sometimes', 'boolean'],
            'is_flexible' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
