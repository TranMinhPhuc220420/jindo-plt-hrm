<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Services\Notification\PushSubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class SendWebPushJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $notificationId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $publicKey = (string) config('webpush.vapid.public_key');
        $privateKey = (string) config('webpush.vapid.private_key');

        if ($publicKey === '' || $privateKey === '') {
            return;
        }

        $notification = Notification::query()->with('user')->find($this->notificationId);

        if ($notification === null || $notification->user === null) {
            return;
        }

        $subscriptions = PushSubscription::query()
            ->where('user_id', $notification->user_id)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $data = is_array($notification->data) ? $notification->data : [];
        $payload = json_encode([
            'title' => $notification->title,
            'body' => $notification->body ?? '',
            'url' => (string) ($data['url'] ?? $data['attendance_url'] ?? '/notifications'),
        ], JSON_THROW_ON_ERROR);

        $subject = (string) (config('webpush.vapid.subject') ?: config('app.url'));

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        foreach ($subscriptions as $row) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $row->endpoint,
                        'publicKey' => $row->public_key,
                        'authToken' => $row->auth_token,
                        'contentEncoding' => $row->content_encoding ?: 'aes128gcm',
                    ]),
                    $payload,
                );
            } catch (Throwable) {
                $row->delete();
            }
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            $status = $report->getResponse()?->getStatusCode();
            $expired = $report->isSubscriptionExpired();

            if (PushSubscriptionService::shouldDropSubscription($status, $expired)) {
                PushSubscription::query()->where('endpoint', $endpoint)->delete();
            }
        }
    }
}
