<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_request_attendance_correction') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendance_record_id' => ['required', 'integer'],
            'proposed_check_in_at' => ['sometimes', 'nullable', 'date'],
            'proposed_check_out_at' => ['sometimes', 'nullable', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
