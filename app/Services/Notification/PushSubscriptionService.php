<?php

namespace App\Services\Notification;

use App\Models\PushSubscription;
use App\Models\User;

class PushSubscriptionService
{
    /**
     * @param  array{endpoint: string, public_key: string, auth_token: string, content_encoding?: string}  $data
     */
    public function upsert(User $user, array $data): PushSubscription
    {
        $encoding = $data['content_encoding'] ?? 'aes128gcm';
        if ($encoding === '') {
            $encoding = 'aes128gcm';
        }

        return PushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id' => $user->id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['public_key'],
                'auth_token' => $data['auth_token'],
                'content_encoding' => $encoding,
            ],
        );
    }

    public function deleteByEndpoint(User $user, string $endpoint): int
    {
        PushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->delete();

        return PushSubscription::query()->where('user_id', $user->id)->count();
    }

    public function deleteAllFor(User $user): void
    {
        PushSubscription::query()->where('user_id', $user->id)->delete();
    }

    public static function shouldDropSubscription(?int $status, bool $expired): bool
    {
        return $expired || in_array($status, [404, 410], true);
    }
}
