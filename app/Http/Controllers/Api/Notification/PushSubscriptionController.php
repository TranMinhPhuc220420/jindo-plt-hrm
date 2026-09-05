<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\DestroyPushSubscriptionRequest;
use App\Http\Requests\Notification\StorePushSubscriptionRequest;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Notification\PushSubscriptionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly PushSubscriptionService $subscriptions,
        private readonly NotificationService $notifications,
    ) {}

    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $keys = $validated['keys'];

        /** @var User $user */
        $user = $request->user();

        $row = $this->subscriptions->upsert($user, [
            'endpoint' => $validated['endpoint'],
            'public_key' => $keys['p256dh'],
            'auth_token' => $keys['auth'],
            'content_encoding' => $validated['content_encoding'] ?? 'aes128gcm',
        ]);

        return ApiResponse::success(
            [
                'id' => $row->id,
                'endpoint' => $row->endpoint,
            ],
            'Push subscription saved.',
        );
    }

    public function destroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $remaining = $this->subscriptions->deleteByEndpoint(
            $user,
            $request->validated('endpoint'),
        );

        if ($remaining === 0) {
            $this->notifications->updatePreferences($user, ['push' => false]);
        }

        return ApiResponse::success(
            ['remaining' => $remaining],
            'Push subscription removed.',
        );
    }
}
