<?php

namespace App\Listeners;

use App\Events\AssetAssigned;
use App\Events\AssetReturned;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class LogAssetNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handleAssigned(AssetAssigned $event): void
    {
        $assignment = $event->assignment;
        Log::info('asset.notification', [
            'type' => 'asset.assigned',
            'assignment_id' => $assignment->id,
            'employee_id' => $assignment->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $assignment->employee_id,
            type: 'asset.assigned',
            data: [
                'asset_assignment_id' => $assignment->id,
                'asset_id' => $assignment->asset_id,
            ],
            companyId: $assignment->company_id,
        );
    }

    public function handleReturned(AssetReturned $event): void
    {
        $assignment = $event->assignment;
        Log::info('asset.notification', [
            'type' => 'asset.returned',
            'assignment_id' => $assignment->id,
            'employee_id' => $assignment->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $assignment->employee_id,
            type: 'asset.returned',
            data: [
                'asset_assignment_id' => $assignment->id,
                'asset_id' => $assignment->asset_id,
            ],
            companyId: $assignment->company_id,
        );
    }
}
