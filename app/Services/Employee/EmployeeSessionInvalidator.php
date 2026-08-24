<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmployeeSessionInvalidator
{
    public function invalidate(Employee $employee): void
    {
        if ($employee->user_id === null) {
            return;
        }

        $actorId = Auth::id();

        if ($actorId !== null && (int) $actorId === (int) $employee->user_id) {
            return;
        }

        $user = $employee->user ?? User::query()->find($employee->user_id);

        if (! $user instanceof User) {
            return;
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $user->setRememberToken(Str::random(60));
        $user->save();
    }
}
