<?php

namespace App\Http\Requests\Authorization;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_roles') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'distinct'],
        ];
    }
}
