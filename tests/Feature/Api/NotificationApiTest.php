<?php

use App\Jobs\SendNotificationEmailJob;
use App\Jobs\SendWebPushJob;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

function notificationUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'notif');
}

test('inbox lists notifications and supports unread filter', function () {
    $company = Company::factory()->create();
    $user = notificationUser(['can_view_own_notifications']);

    Notification::factory()->count(2)->create(['user_id' => $user->id, 'company_id' => $company->id]);
    Notification::factory()->read()->create(['user_id' => $user->id, 'company_id' => $company->id]);
    Notification::factory()->create(['company_id' => $company->id]); // other user

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/notifications?unread_only=1')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('unread count endpoint returns count', function () {
    $company = Company::factory()->create();
    $user = notificationUser(['can_view_own_notifications']);
    Notification::factory()->count(3)->create(['user_id' => $user->id, 'company_id' => $company->id]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.unread_count', 3);
});

test('mark one and all read', function () {
    $company = Company::factory()->create();
    $user = notificationUser(['can_view_own_notifications']);
    $notifications = Notification::factory()->count(3)->create(['user_id' => $user->id, 'company_id' => $company->id]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/'.$notifications[0]->id.'/read')
        ->assertOk();

    expect(Notification::query()->whereNull('read_at')->count())->toBe(2);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/read-all')
        ->assertOk();

    expect(Notification::query()->whereNull('read_at')->count())->toBe(0);
});

test('delete removes a notification and blocks foreign ids', function () {
    $company = Company::factory()->create();
    $user = notificationUser(['can_view_own_notifications']);
    $own = Notification::factory()->create(['user_id' => $user->id, 'company_id' => $company->id]);
    $foreign = Notification::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->deleteJson('/api/notifications/'.$own->id)
        ->assertOk();

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->deleteJson('/api/notifications/'.$foreign->id)
        ->assertStatus(404)
        ->assertJsonPath('error_code', 'NOTIFICATION_NOT_FOUND');
});

test('preferences can be read and updated', function () {
    $company = Company::factory()->create();
    $user = notificationUser(['can_view_own_notifications']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/notification-preferences')
        ->assertOk()
        ->assertJsonPath('data.email', true);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->putJson('/api/notification-preferences', [
            'email' => false,
            'categories' => ['leave' => ['email' => true, 'system' => true]],
        ])
        ->assertOk()
        ->assertJsonPath('data.email', false);

    expect(NotificationPreference::query()->where('user_id', $user->id)->first()->email)->toBeFalse();
});

test('requires permission to view inbox', function () {
    Company::factory()->create();
    $user = notificationUser([]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/notifications')
        ->assertStatus(403);
});

test('leave event lands in the requester inbox and notifies the manager', function () {
    $company = Company::factory()->create();

    $managerUser = notificationUser([
        'can_approve_leave',
        'can_view_leave',
        'can_view_own_notifications',
    ]);
    $manager = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $managerUser->id,
        'status' => 'active',
    ]);

    $user = notificationUser(['can_request_leave', 'can_view_leave', 'can_view_own_notifications']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'manager_id' => $manager->id,
        'status' => 'active',
    ]);
    $leaveType = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
        ])
        ->assertCreated();

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.type', 'leave.requested');

    expect(Notification::query()->where('user_id', $managerUser->id)->where('type', 'leave.pending_approval')->count())
        ->toBe(1);
});

test('creating a notification queues an email job when the user opts in', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $sender = notificationUser(['can_send_broadcast_notification', 'can_view_own_notifications']);
    $recipientUser = notificationUser(['can_view_own_notifications']);
    Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $recipientUser->id,
        'status' => 'active',
    ]);

    $this->actingAs($sender)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/broadcast', [
            'title' => 'Town hall',
            'body' => 'Friday 3pm',
        ])
        ->assertOk();

    $notificationId = Notification::query()->where('user_id', $recipientUser->id)->value('id');

    Queue::assertPushedOn('notifications', SendNotificationEmailJob::class, fn ($job) => $job->notificationId === $notificationId);
});

test('no email job is queued when the user opts out of email', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $sender = notificationUser(['can_send_broadcast_notification', 'can_view_own_notifications']);
    $recipientUser = notificationUser(['can_view_own_notifications']);
    Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $recipientUser->id,
        'status' => 'active',
    ]);
    NotificationPreference::query()->create([
        'user_id' => $recipientUser->id,
        'email' => false,
        'push' => false,
        'system' => true,
    ]);

    $this->actingAs($sender)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/broadcast', [
            'title' => 'Town hall',
            'body' => 'Friday 3pm',
        ])
        ->assertOk();

    Queue::assertNotPushed(SendNotificationEmailJob::class);
});

test('broadcast requires permission and fans out to company employees', function () {
    $company = Company::factory()->create();

    $sender = notificationUser([
        'can_send_broadcast_notification',
        'can_view_own_notifications',
    ]);
    $recipientUser = notificationUser(['can_view_own_notifications']);
    Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $recipientUser->id,
        'status' => 'active',
    ]);

    $this->actingAs(notificationUser(['can_view_own_notifications']))
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/broadcast', [
            'title' => 'Town hall',
            'body' => 'Friday 3pm',
        ])
        ->assertForbidden();

    $this->actingAs($sender)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/broadcast', [
            'title' => 'Town hall',
            'body' => 'Friday 3pm',
        ])
        ->assertOk()
        ->assertJsonPath('data.sent', 1);

    expect(Notification::query()
        ->where('user_id', $recipientUser->id)
        ->where('type', 'broadcast.announcement')
        ->count())->toBe(1);
});

test('test push requires broadcast permission', function () {
    Company::factory()->create();
    $user = notificationUser(['can_view_own_notifications']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/test-push')
        ->assertForbidden();
});

test('test push requires vapid keys', function () {
    Company::factory()->create();
    $user = notificationUser(['can_send_broadcast_notification', 'can_view_own_notifications']);
    config(['webpush.vapid.public_key' => '', 'webpush.vapid.private_key' => '']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/test-push')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PUSH_VAPID_NOT_CONFIGURED');
});

test('test push requires a browser subscription', function () {
    Company::factory()->create();
    $user = notificationUser(['can_send_broadcast_notification', 'can_view_own_notifications']);
    config([
        'webpush.vapid.public_key' => 'test-public',
        'webpush.vapid.private_key' => 'test-private',
    ]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/test-push')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PUSH_SUBSCRIPTION_MISSING');
});

test('test push creates an inbox row and sends web push immediately', function () {
    Bus::fake([SendWebPushJob::class]);

    Company::factory()->create();
    $user = notificationUser(['can_send_broadcast_notification', 'can_view_own_notifications']);
    config([
        'webpush.vapid.public_key' => 'test-public',
        'webpush.vapid.private_key' => 'test-private',
    ]);
    PushSubscription::query()->create([
        'user_id' => $user->id,
        'endpoint' => 'https://push.example.test/test',
        'public_key' => 'pk',
        'auth_token' => 'at',
        'content_encoding' => 'aes128gcm',
    ]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/notifications/test-push')
        ->assertOk()
        ->assertJsonPath('data.type', 'push.test');

    expect(Notification::query()->where('user_id', $user->id)->where('type', 'push.test')->count())->toBe(1);

    Bus::assertDispatched(SendWebPushJob::class);
});
