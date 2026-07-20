<?php

namespace App\Listeners;

use App\Events\AttendanceCorrectionApproved;
use App\Events\AttendanceCorrectionRejected;
use App\Events\AttendanceCorrectionRequested;
use App\Models\Employee;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class LogAttendanceNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handleCorrectionRequested(AttendanceCorrectionRequested $event): void
    {
        $correction = $event->correction;
        Log::info('attendance.notification', [
            'type' => 'attendance.correction_requested',
            'correction_id' => $correction->id,
            'employee_id' => $correction->employee_id,
        ]);

        $data = [
            'attendance_correction_id' => $correction->id,
            'attendance_record_id' => $correction->attendance_record_id,
        ];

        $employee = Employee::query()->find($correction->employee_id);
        if ($employee === null) {
            return;
        }

        foreach ($this->recipients->managerOrPermissionHolders($employee, 'can_approve_attendance') as $approver) {
            if ($employee->user_id !== null && $approver->id === $employee->user_id) {
                continue;
            }

            $this->notifications->notify(
                user: $approver,
                type: 'attendance.correction_requested',
                data: $data,
                companyId: $correction->company_id,
            );
        }
    }

    public function handleCorrectionApproved(AttendanceCorrectionApproved $event): void
    {
        $correction = $event->correction;
        Log::info('attendance.notification', [
            'type' => 'attendance.correction_approved',
            'correction_id' => $correction->id,
            'employee_id' => $correction->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $correction->employee_id,
            type: 'attendance.correction_approved',
            data: [
                'attendance_correction_id' => $correction->id,
                'attendance_record_id' => $correction->attendance_record_id,
            ],
            companyId: $correction->company_id,
        );
    }

    public function handleCorrectionRejected(AttendanceCorrectionRejected $event): void
    {
        $correction = $event->correction;
        Log::info('attendance.notification', [
            'type' => 'attendance.correction_rejected',
            'correction_id' => $correction->id,
            'employee_id' => $correction->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $correction->employee_id,
            type: 'attendance.correction_rejected',
            data: [
                'attendance_correction_id' => $correction->id,
                'attendance_record_id' => $correction->attendance_record_id,
            ],
            companyId: $correction->company_id,
        );
    }
}
