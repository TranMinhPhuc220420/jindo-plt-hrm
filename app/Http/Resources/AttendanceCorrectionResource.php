<?php

namespace App\Http\Resources;

use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AttendanceCorrection
 */
class AttendanceCorrectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'attendance_record_id' => $this->attendance_record_id,
            'employee_id' => $this->employee_id,
            'proposed_check_in_at' => $this->proposed_check_in_at?->toIso8601String(),
            'proposed_check_out_at' => $this->proposed_check_out_at?->toIso8601String(),
            'reason' => $this->reason,
            'status' => $this->status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_note' => $this->review_note,
            'record' => $this->whenLoaded('record', fn () => $this->record
                ? (new AttendanceRecordResource($this->record))->resolve()
                : null),
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
