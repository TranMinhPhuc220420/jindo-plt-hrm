<?php

use App\Events\LeaveApproved;
use App\Listeners\LogLeaveNotification;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('LogLeaveNotification handleApproved creates an inbox notification for the employee user', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    $type = LeaveType::factory()->create(['company_id' => $company->id]);
    $request = LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'status' => 'approved',
    ]);

    app(LogLeaveNotification::class)->handleApproved(new LeaveApproved($request));

    $notification = Notification::query()
        ->where('user_id', $user->id)
        ->where('type', 'leave.approved')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->company_id)->toBe($company->id)
        ->and($notification->data['leave_request_id'] ?? null)->toBe($request->id);
});
