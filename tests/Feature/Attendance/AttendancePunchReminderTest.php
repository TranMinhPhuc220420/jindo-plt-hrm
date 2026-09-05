<?php

use App\Jobs\SendWebPushJob;
use App\Models\AttendancePunchReminder;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Services\Attendance\AttendancePunchReminderService;
use App\Services\Notification\NotificationService;
use App\Services\Notification\PushSubscriptionService;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;

function reminderShiftEmployee(Company $company, array $shiftAttrs = []): Employee
{
    $user = actingUser(['can_view_own_notifications'], prefix: 'punchrem');
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'status' => 'active',
        'code' => 'E-REM-'.uniqid(),
    ]);

    $shift = Shift::factory()->create(array_merge([
        'company_id' => $company->id,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_night' => false,
        'kind' => 'standard',
    ], $shiftAttrs));

    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2020-01-01',
        'end_date' => null,
    ]);

    return $employee->fresh(['user']) ?? $employee;
}

test('sends a check-in reminder after grace minutes when the employee has not punched', function () {
    $company = Company::factory()->create();
    $employee = reminderShiftEmployee($company);

    $sent = app(AttendancePunchReminderService::class)->sendDue(
        CarbonImmutable::parse('2026-07-16 08:10:00', 'Asia/Ho_Chi_Minh'),
    );

    expect($sent)->toBe(1)
        ->and(Notification::query()->where('type', 'attendance.check_in_reminder')->count())->toBe(1)
        ->and(AttendancePunchReminder::query()->where('kind', 'check_in')->count())->toBe(1)
        ->and(Notification::query()->value('user_id'))->toBe($employee->user_id);
});

test('does not send a check-in reminder when already checked in', function () {
    $company = Company::factory()->create();
    $employee = reminderShiftEmployee($company);
    $shiftId = ShiftAssignment::query()->where('employee_id', $employee->id)->value('shift_id');

    AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shiftId,
        'work_date' => '2026-07-16',
        'check_in_at' => '2026-07-16 08:00:00',
        'check_out_at' => null,
        'status' => 'open',
    ]);

    $sent = app(AttendancePunchReminderService::class)->sendDue(
        CarbonImmutable::parse('2026-07-16 08:10:00', 'Asia/Ho_Chi_Minh'),
    );

    expect($sent)->toBe(0)
        ->and(Notification::query()->where('type', 'attendance.check_in_reminder')->count())->toBe(0);
});

test('sends a check-out reminder after shift end when still open', function () {
    $company = Company::factory()->create();
    $employee = reminderShiftEmployee($company);
    $shiftId = ShiftAssignment::query()->where('employee_id', $employee->id)->value('shift_id');

    AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shiftId,
        'work_date' => '2026-07-16',
        'check_in_at' => '2026-07-16 08:00:00',
        'check_out_at' => null,
        'status' => 'open',
    ]);

    $sent = app(AttendancePunchReminderService::class)->sendDue(
        CarbonImmutable::parse('2026-07-16 17:15:00', 'Asia/Ho_Chi_Minh'),
    );

    expect($sent)->toBe(1)
        ->and(Notification::query()->where('type', 'attendance.check_out_reminder')->count())->toBe(1);
});

test('skips holidays and full-day approved leave', function () {
    $company = Company::factory()->create();
    reminderShiftEmployee($company);

    Holiday::factory()->create([
        'company_id' => $company->id,
        'date' => '2026-07-16',
        'name' => 'Test holiday',
    ]);

    $sentHoliday = app(AttendancePunchReminderService::class)->sendDue(
        CarbonImmutable::parse('2026-07-16 08:10:00', 'Asia/Ho_Chi_Minh'),
    );

    expect($sentHoliday)->toBe(0);

    $company2 = Company::factory()->create();
    $employee = reminderShiftEmployee($company2);
    $leaveType = LeaveType::factory()->create([
        'company_id' => $company2->id,
        'requires_balance' => false,
    ]);
    LeaveRequest::factory()->create([
        'company_id' => $company2->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'unit' => 'day',
        'start_date' => '2026-07-16',
        'end_date' => '2026-07-16',
        'status' => 'approved',
        'is_half_day' => false,
    ]);

    $sentLeave = app(AttendancePunchReminderService::class)->sendDue(
        CarbonImmutable::parse('2026-07-16 08:10:00', 'Asia/Ho_Chi_Minh'),
    );

    expect($sentLeave)->toBe(0);
});

test('sends at most one reminder per kind per window', function () {
    $company = Company::factory()->create();
    reminderShiftEmployee($company);
    $service = app(AttendancePunchReminderService::class);
    $now = CarbonImmutable::parse('2026-07-16 08:10:00', 'Asia/Ho_Chi_Minh');

    expect($service->sendDue($now))->toBe(1)
        ->and($service->sendDue($now))->toBe(0)
        ->and(Notification::query()->where('type', 'attendance.check_in_reminder')->count())->toBe(1);
});

test('night shift check-out reminder is due after midnight end plus grace', function () {
    $company = Company::factory()->create();
    $employee = reminderShiftEmployee($company, [
        'start_time' => '22:00:00',
        'end_time' => '06:00:00',
        'is_night' => true,
        'kind' => 'night',
    ]);
    $shiftId = ShiftAssignment::query()->where('employee_id', $employee->id)->value('shift_id');

    AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shiftId,
        'work_date' => '2026-07-16',
        'check_in_at' => '2026-07-16 22:05:00',
        'check_out_at' => null,
        'status' => 'open',
    ]);

    $sent = app(AttendancePunchReminderService::class)->sendDue(
        CarbonImmutable::parse('2026-07-17 06:15:00', 'Asia/Ho_Chi_Minh'),
    );

    expect($sent)->toBe(1)
        ->and(Notification::query()->where('type', 'attendance.check_out_reminder')->count())->toBe(1);
});

test('disabled company setting skips reminders', function () {
    $company = Company::factory()->create();
    reminderShiftEmployee($company);
    app(SettingsService::class)->seedDefaultsForCompany($company->id);
    app(SettingsService::class)->update([
        'attendance' => ['punch_reminder_enabled' => false],
    ]);

    $sent = app(AttendancePunchReminderService::class)->sendDue(
        CarbonImmutable::parse('2026-07-16 08:10:00', 'Asia/Ho_Chi_Minh'),
    );

    expect($sent)->toBe(0);
});

test('artisan command sends due reminders', function () {
    $company = Company::factory()->create();
    reminderShiftEmployee($company);

    $this->travelTo(CarbonImmutable::parse('2026-07-16 08:10:00', 'Asia/Ho_Chi_Minh'));

    $this->artisan('attendance:send-punch-reminders')
        ->assertSuccessful();

    expect(Notification::query()->where('type', 'attendance.check_in_reminder')->count())->toBe(1);
});

test('push subscription can be registered and removed', function () {
    Company::factory()->create();
    $user = actingUser(['can_view_own_notifications'], prefix: 'pushsub');

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://push.example.test/sub/1',
            'keys' => [
                'p256dh' => 'public-key-value',
                'auth' => 'auth-token-value',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.endpoint', 'https://push.example.test/sub/1');

    expect(PushSubscription::query()->where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->deleteJson('/api/push-subscriptions', [
            'endpoint' => 'https://push.example.test/sub/1',
        ])
        ->assertOk()
        ->assertJsonPath('data.remaining', 0);

    expect(PushSubscription::query()->count())->toBe(0);
});

test('two browsers can register push and notify still queues one job', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = actingUser(['can_view_own_notifications'], prefix: 'push2dev');
    NotificationPreference::query()->create([
        'user_id' => $user->id,
        'email' => false,
        'push' => true,
        'system' => true,
    ]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://push.example.test/pc',
            'keys' => ['p256dh' => 'pk-pc', 'auth' => 'at-pc'],
        ])
        ->assertOk();

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://push.example.test/phone',
            'keys' => ['p256dh' => 'pk-phone', 'auth' => 'at-phone'],
        ])
        ->assertOk();

    expect(PushSubscription::query()->where('user_id', $user->id)->count())->toBe(2);

    $notification = app(NotificationService::class)->notify(
        user: $user,
        type: 'attendance.check_in_reminder',
        companyId: $company->id,
    );

    Queue::assertPushedOn('notifications', SendWebPushJob::class, fn ($job) => $job->notificationId === $notification->id);
    Queue::assertPushed(SendWebPushJob::class, 1);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->deleteJson('/api/push-subscriptions', [
            'endpoint' => 'https://push.example.test/pc',
        ])
        ->assertOk()
        ->assertJsonPath('data.remaining', 1);

    expect(NotificationPreference::query()->where('user_id', $user->id)->value('push'))->toBeTrue()
        ->and(PushSubscription::query()->where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->deleteJson('/api/push-subscriptions', [
            'endpoint' => 'https://push.example.test/phone',
        ])
        ->assertOk()
        ->assertJsonPath('data.remaining', 0);

    expect(NotificationPreference::query()->where('user_id', $user->id)->value('push'))->toBeFalse();
});

test('push subscription accepts a long mozilla-style endpoint', function () {
    Company::factory()->create();
    $user = actingUser(['can_view_own_notifications'], prefix: 'pushlong');
    $endpoint = 'https://updates.push.services.mozilla.com/wpush/v2/'.str_repeat('A', 1800);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/push-subscriptions', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'pk', 'auth' => 'at'],
        ])
        ->assertOk()
        ->assertJsonPath('data.endpoint', $endpoint);
});

test('notify queues web push when the user opted in and has a subscription', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = actingUser(['can_view_own_notifications'], prefix: 'pushjob');
    PushSubscription::query()->create([
        'user_id' => $user->id,
        'endpoint' => 'https://push.example.test/sub/2',
        'public_key' => 'pk',
        'auth_token' => 'at',
        'content_encoding' => 'aes128gcm',
    ]);
    NotificationPreference::query()->create([
        'user_id' => $user->id,
        'email' => false,
        'push' => true,
        'system' => true,
    ]);

    $notification = app(NotificationService::class)->notify(
        user: $user,
        type: 'attendance.check_in_reminder',
        companyId: $company->id,
    );

    Queue::assertPushedOn('notifications', SendWebPushJob::class, fn ($job) => $job->notificationId === $notification->id);
});

test('expired web push status codes drop the subscription', function () {
    expect(PushSubscriptionService::shouldDropSubscription(410, false))->toBeTrue()
        ->and(PushSubscriptionService::shouldDropSubscription(404, false))->toBeTrue()
        ->and(PushSubscriptionService::shouldDropSubscription(201, true))->toBeTrue()
        ->and(PushSubscriptionService::shouldDropSubscription(201, false))->toBeFalse();
});
