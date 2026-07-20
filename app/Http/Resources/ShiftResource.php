<?php

namespace App\Http\Resources;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shift
 */
class ShiftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'code' => $this->code,
            'start_time' => $this->formatTime($this->start_time),
            'end_time' => $this->formatTime($this->end_time),
            'break_minutes' => $this->break_minutes,
            'kind' => $this->kind,
            'is_night' => $this->is_night,
            'is_flexible' => $this->is_flexible,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function formatTime(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        $raw = (string) $time;

        return strlen($raw) >= 5 ? substr($raw, 0, 5) : $raw;
    }
}
