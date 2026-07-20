<?php

namespace App\Services\Notification;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves notification recipients for manager / permission-based fan-out.
 */
class NotificationRecipientResolver
{
    /**
     * Prefer the employee's manager user; fall back to users with $permission
     * who belong to the same company via their employee record.
     *
     * @return Collection<int, User>
     */
    public function managerOrPermissionHolders(Employee $employee, string $permission): Collection
    {
        $employee->loadMissing('manager.user');

        if ($employee->manager?->user !== null) {
            return collect([$employee->manager->user]);
        }

        return $this->usersWithPermissionInCompany($employee->company_id, $permission);
    }

    /**
     * @return Collection<int, User>
     */
    public function usersWithPermissionInCompany(int $companyId, string $permission): Collection
    {
        return User::query()
            ->whereHas('roles.permissions', fn ($q) => $q->where('key', $permission))
            ->whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->get();
    }

    /**
     * The employee's linked user and optionally their manager.
     *
     * @return Collection<int, User>
     */
    public function employeeWithManager(Employee $employee, bool $includeManager = false): Collection
    {
        $employee->loadMissing(['user', 'manager.user']);

        $users = collect();

        if ($employee->user !== null) {
            $users->push($employee->user);
        }

        if ($includeManager && $employee->manager?->user !== null) {
            $users->push($employee->manager->user);
        }

        return $users->unique('id')->values();
    }
}
