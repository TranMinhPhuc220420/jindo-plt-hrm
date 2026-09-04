<?php

namespace App\Http\Resources;

use App\Models\Employee;
use App\Services\Employee\EmployeeStatusTransitions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'team_id' => $this->team_id,
            'position_id' => $this->position_id,
            'manager_id' => $this->manager_id,
            'supervisor_id' => $this->supervisor_id,
            'hr_owner_id' => $this->hr_owner_id,
            'user_id' => $this->user_id,
            'hired_at' => $this->hired_at?->toDateString(),
            'terminated_at' => $this->terminated_at?->toDateString(),
            'status' => $this->status,
            'allowed_next_statuses' => EmployeeStatusTransitions::allowedNextStatuses($this->status),
            'avatar_url' => $this->avatarUrl(),
            'outstanding_assets' => $this->whenLoaded(
                'activeAssetAssignments',
                fn () => $this->activeAssetAssignments
                    ->map(fn ($assignment) => [
                        'id' => $assignment->asset?->id ?? $assignment->asset_id,
                        'code' => $assignment->asset?->code,
                        'name' => $assignment->asset?->name,
                    ])
                    ->values()
                    ->all(),
            ),
            'outstanding_assets_count' => $this->when(
                $this->relationLoaded('activeAssetAssignments'),
                fn () => $this->activeAssetAssignments->count(),
            ),
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
            ] : null),
            'position' => $this->whenLoaded('position', fn () => $this->position ? [
                'id' => $this->position->id,
                'name' => $this->position->name,
                'code' => $this->position->code,
            ] : null),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
