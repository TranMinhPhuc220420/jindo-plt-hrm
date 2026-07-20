<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_attendance');
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        return $user->can('can_view_attendance');
    }

    public function checkInOut(User $user): bool
    {
        return $user->can('can_check_in_out');
    }

    public function approve(User $user, AttendanceRecord $record): bool
    {
        return $user->can('can_approve_attendance');
    }

    public function manage(User $user): bool
    {
        return $user->can('can_manage_attendance');
    }
}
