<?php

namespace App\Listeners;

use App\Events\EmployeeStatusChanged;
use App\Models\Employee;
use App\Services\Employee\EmployeeSessionInvalidator;

class InvalidateEmployeeSessions
{
    public function __construct(
        private readonly EmployeeSessionInvalidator $sessions,
    ) {}

    public function handle(EmployeeStatusChanged $event): void
    {
        if (! in_array($event->to, Employee::LOGIN_BLOCKED_STATUSES, true)) {
            return;
        }

        $this->sessions->invalidate($event->employee);
    }
}
