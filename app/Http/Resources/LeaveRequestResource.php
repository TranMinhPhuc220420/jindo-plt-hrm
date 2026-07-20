<?php

namespace App\Http\Resources;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveRequest
 */
class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'employee_code' => $this->whenLoaded('employee', fn () => $this->employee?->code),
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->full_name ?? $this->employee?->name),
            'leave_type_id' => $this->leave_type_id,
            'leave_type_name' => $this->whenLoaded('leaveType', fn () => $this->leaveType?->name),
            'leave_type_code' => $this->whenLoaded('leaveType', fn () => $this->leaveType?->code),
            'unit' => $this->unit,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'is_half_day' => $this->is_half_day,
            'half_day_period' => $this->half_day_period,
            'quantity' => (float) $this->quantity,
            'reason' => $this->reason,
            'status' => $this->status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_note' => $this->review_note,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
