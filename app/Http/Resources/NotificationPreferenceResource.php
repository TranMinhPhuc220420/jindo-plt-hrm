<?php

namespace App\Http\Resources;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NotificationPreference
 */
class NotificationPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'email' => (bool) $this->email,
            'push' => (bool) $this->push,
            'system' => (bool) $this->system,
            'categories' => $this->categories ?? [],
        ];
    }
}
