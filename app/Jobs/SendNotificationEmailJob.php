<?php

namespace App\Jobs;

use App\Mail\NotificationMail;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers a notification by email via Mailable.
 */
class SendNotificationEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $notificationId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $notification = Notification::query()->with('user')->find($this->notificationId);

        if ($notification === null || $notification->user === null) {
            return;
        }

        $email = $notification->user->email;
        if ($email === '') {
            return;
        }

        Mail::to($email)->send(new NotificationMail($notification));
    }
}
