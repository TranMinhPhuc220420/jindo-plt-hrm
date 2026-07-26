<?php

namespace App\Http\Requests\Attendance;

use App\Exceptions\DomainException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
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
            'source' => ['sometimes', 'string', 'in:manual'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'address' => ['required', 'string', 'max:500'],
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'captured_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Location latitude is required for check-in.',
            'longitude.required' => 'Location longitude is required for check-in.',
            'address.required' => 'Location address is required for check-in.',
            'photo.required' => 'A camera photo is required for check-in.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new DomainException(
            message: 'Location and camera photo are required to record attendance.',
            errorCode: 'ATTENDANCE_EVIDENCE_REQUIRED',
            status: 422,
            errors: $validator->errors()->toArray(),
        );
    }
}
