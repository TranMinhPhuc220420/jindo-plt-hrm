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
            'latitude.required' => 'Location latitude is required for check-out.',
            'longitude.required' => 'Location longitude is required for check-out.',
            'address.required' => 'Location address is required for check-out.',
            'photo.required' => 'A camera photo is required for check-out.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new \App\Exceptions\DomainException(
            message: 'Location and camera photo are required to record attendance.',
            errorCode: 'ATTENDANCE_EVIDENCE_REQUIRED',
            status: 422,
            errors: $validator->errors()->toArray(),
        );
    }
}
