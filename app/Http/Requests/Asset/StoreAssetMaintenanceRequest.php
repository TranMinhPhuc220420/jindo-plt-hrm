<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:191'],
            'status' => ['nullable', 'string', 'in:scheduled,in_progress,completed,cancelled'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'scheduled_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
