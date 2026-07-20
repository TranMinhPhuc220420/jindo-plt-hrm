<?php

namespace App\Services\Employee;

use App\Models\Employee;

final class EmployeeStatusTransitions
{
    /**
     * Allowed from → to transitions.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED = [
        'probation' => ['active', 'suspended', 'resigned'],
        'active' => ['suspended', 'resigned'],
        'suspended' => ['active', 'resigned'],
        'resigned' => ['archived'],
        'archived' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        if (! in_array($from, Employee::STATUSES, true) || ! in_array($to, Employee::STATUSES, true)) {
            return false;
        }

        if ($from === $to) {
            return true;
        }

        return in_array($to, self::ALLOWED[$from], true);
    }
}
