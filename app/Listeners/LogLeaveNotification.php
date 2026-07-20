<?php

namespace App\Listeners;

use App\Events\LeaveApproved;
use App\Events\LeaveCancelled;
use App\Events\LeaveRejected;
use App\Events\LeaveRequested;
use App\Models\Employee;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Fans leave domain events into the notification inbox (and queued email).
 */
class LogLeaveNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handleRequested(LeaveRequested $event): void
    {
        $request = $event->leaveRequest;
        Log::info('leave.notification', [
            'type' => 'leave.requested',
            'leave_request_id' => $request->id,
            'employee_id' => $request->employee_id,
        ]);

        $data = ['leave_request_id' => $request->id];

        $this->notifications->notifyEmployee(
            employeeId: $request->employee_id,
            type: 'leave.requested',
            data: $data,
            companyId: $request->company_id,
        );

        $employee = Employee::query()->find($request->employee_id);
        if ($employee === null) {
            return;
        }

        foreach ($this->recipients->managerOrPermissionHolders($employee, 'can_approve_leave') as $approver) {
            $this->notifications->notify(
                user: $approver,
                type: 'leave.pending_approval',
                data: $data,
                companyId: $request->company_id,
            );
        }
    }

    public function handleApproved(LeaveApproved $event): void
    {
        $request = $event->leaveRequest;
        Log::info('leave.notification', [
            'type' => 'leave.approved',
            'leave_request_id' => $request->id,
            'employee_id' => $request->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $request->employee_id,
            type: 'leave.approved',
            data: ['leave_request_id' => $request->id],
            companyId: $request->company_id,
        );
    }

    public function handleRejected(LeaveRejected $event): void
    {
        $request = $event->leaveRequest;
        Log::info('leave.notification', [
            'type' => 'leave.rejected',
            'leave_request_id' => $request->id,
            'employee_id' => $request->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $request->employee_id,
            type: 'leave.rejected',
            data: ['leave_request_id' => $request->id],
            companyId: $request->company_id,
        );
    }

    public function handleCancelled(LeaveCancelled $event): void
    {
        $request = $event->leaveRequest;
        Log::info('leave.notification', [
            'type' => 'leave.cancelled',
            'leave_request_id' => $request->id,
            'employee_id' => $request->employee_id,
        ]);

        $data = ['leave_request_id' => $request->id];

        $this->notifications->notifyEmployee(
            employeeId: $request->employee_id,
            type: 'leave.cancelled',
            data: $data,
            companyId: $request->company_id,
        );

        $employee = Employee::query()->find($request->employee_id);
        if ($employee === null) {
            return;
        }

        foreach ($this->recipients->managerOrPermissionHolders($employee, 'can_approve_leave') as $approver) {
            // Skip if approver is the same user as the requester (edge case).
            if ($employee->user_id !== null && $approver->id === $employee->user_id) {
                continue;
            }

            $this->notifications->notify(
                user: $approver,
                type: 'leave.cancelled_pending',
                data: $data,
                companyId: $request->company_id,
            );
        }
    }
}
