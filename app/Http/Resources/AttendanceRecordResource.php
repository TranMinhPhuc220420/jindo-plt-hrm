<?php

namespace App\Http\Resources;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AttendanceRecord
 */
class AttendanceRecordResource extends JsonResource
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
            'work_date' => $this->work_date?->toDateString(),
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'check_out_at' => $this->check_out_at?->toIso8601String(),
            'worked_minutes' => $this->worked_minutes,
            'late_minutes' => $this->late_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,
            'overtime_minutes' => $this->overtime_minutes,
            'break_minutes' => $this->break_minutes,
            'status' => $this->status,
            'source' => $this->source,
            'note' => $this->note,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'code' => $this->employee->code,
                'full_name' => $this->employee->full_name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
