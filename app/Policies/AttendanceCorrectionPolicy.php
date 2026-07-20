<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;

class AttendanceCorrectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_attendance');
    }

    public function create(User $user): bool
    {
        return $user->can('can_request_attendance_correction');
    }

    public function approve(User $user, AttendanceCorrection $correction): bool
    {
        return $user->can('can_approve_attendance');
    }
}
