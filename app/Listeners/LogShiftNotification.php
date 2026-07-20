<?php

namespace App\Listeners;

use App\Events\ShiftAssigned;
use App\Events\ShiftAssignmentChanged;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class LogShiftNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handleAssigned(ShiftAssigned $event): void
    {
        $assignment = $event->assignment;
        Log::info('shift.notification', [
            'type' => 'shift.assigned',
            'shift_assignment_id' => $assignment->id,
            'employee_id' => $assignment->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $assignment->employee_id,
            type: 'shift.assigned',
            data: [
                'shift_assignment_id' => $assignment->id,
                'shift_id' => $assignment->shift_id,
            ],
            companyId: $assignment->company_id,
        );
    }

    public function handleChanged(ShiftAssignmentChanged $event): void
    {
        $assignment = $event->assignment;
        Log::info('shift.notification', [
            'type' => 'shift.changed',
            'shift_assignment_id' => $assignment->id,
            'employee_id' => $assignment->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $assignment->employee_id,
            type: 'shift.changed',
            data: [
                'shift_assignment_id' => $assignment->id,
                'shift_id' => $assignment->shift_id,
            ],
            companyId: $assignment->company_id,
        );
    }
}
