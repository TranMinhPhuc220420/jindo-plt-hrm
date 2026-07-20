<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_view_own_notifications') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['sometimes', 'boolean'],
            'push' => ['sometimes', 'boolean'],
            'system' => ['sometimes', 'boolean'],
            'categories' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
