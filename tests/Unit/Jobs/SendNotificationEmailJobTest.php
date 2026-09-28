<?php

use App\Jobs\SendNotificationEmailJob;
use App\Mail\NotificationMail;
use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('SendNotificationEmailJob sends NotificationMail to the user', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['email' => 'notify@example.test']);
    $notification = Notification::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'type' => 'leave.approved',
        'title' => 'Leave approved',
        'body' => 'Your leave was approved.',
    ]);

    (new SendNotificationEmailJob($notification->id))->handle();

    Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($user): bool {
        return $mail->hasTo($user->email);
    });
});

test('SendNotificationEmailJob no-ops when notification is missing', function () {
    Mail::fake();

    (new SendNotificationEmailJob(999_999))->handle();

    Mail::assertNothingSent();
});
