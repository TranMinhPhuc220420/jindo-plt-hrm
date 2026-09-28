<?php

use App\Jobs\SendWebPushJob;
use App\Models\Company;
use App\Models\Notification;
use App\Models\User;

test('SendWebPushJob returns early when VAPID keys are empty', function () {
    config(['webpush.vapid.public_key' => '', 'webpush.vapid.private_key' => '']);

    $company = Company::factory()->create();
    $user = User::factory()->create();
    $notification = Notification::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'type' => 'attendance.check_in_reminder',
        'title' => 'Check in',
    ]);

    // Must not throw when WebPush cannot run in CI without VAPID keys.
    (new SendWebPushJob($notification->id))->handle();

    expect(true)->toBeTrue();
});

test('SendWebPushJob no-ops when notification is missing', function () {
    config([
        'webpush.vapid.public_key' => 'BAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
        'webpush.vapid.private_key' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
    ]);

    (new SendWebPushJob(999_999))->handle();

    expect(true)->toBeTrue();
});
