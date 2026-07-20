<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use App\Events\EmployeeStatusChanged;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class LogEmployeeNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handleCreated(EmployeeCreated $event): void
    {
        $employee = $event->employee;
        Log::info('employee.notification', [
            'type' => 'employee.created',
            'employee_id' => $employee->id,
        ]);

        $data = ['employee_id' => $employee->id];

        $this->notifications->notifyEmployee(
            employeeId: $employee->id,
            type: 'employee.created',
            data: $data,
            companyId: $employee->company_id,
        );

        foreach ($this->recipients->usersWithPermissionInCompany($employee->company_id, 'can_view_employee') as $hr) {
            if ($employee->user_id !== null && $hr->id === $employee->user_id) {
                continue;
            }

            $this->notifications->notify(
                user: $hr,
                type: 'employee.created_hr',
                data: $data,
                companyId: $employee->company_id,
            );
        }
    }

    public function handleStatusChanged(EmployeeStatusChanged $event): void
    {
        $employee = $event->employee;
        Log::info('employee.notification', [
            'type' => 'employee.status_changed',
            'employee_id' => $employee->id,
            'from' => $event->from,
            'to' => $event->to,
        ]);

        $isExit = in_array($event->to, ['resigned', 'archived', 'suspended'], true);
        $data = [
            'employee_id' => $employee->id,
            'from' => $event->from,
            'to' => $event->to,
        ];

        $this->notifications->notifyEmployee(
            employeeId: $employee->id,
            type: 'employee.status_changed',
            data: $data,
            companyId: $employee->company_id,
        );

        if ($isExit) {
            foreach ($this->recipients->employeeWithManager($employee, includeManager: true) as $user) {
                if ($employee->user_id !== null && $user->id === $employee->user_id) {
                    continue;
                }

                $this->notifications->notify(
                    user: $user,
                    type: 'employee.status_changed',
                    data: $data,
                    companyId: $employee->company_id,
                );
            }
        }
    }
}
