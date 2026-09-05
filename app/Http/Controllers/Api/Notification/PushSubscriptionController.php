<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\DestroyPushSubscriptionRequest;
use App\Http\Requests\Notification\StorePushSubscriptionRequest;
use App\Services\Notification\PushSubscriptionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly PushSubscriptionService $subscriptions,
    ) {}

    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $keys = $validated['keys'];

        $row = $this->subscriptions->upsert($request->user(), [
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
        $this->subscriptions->deleteByEndpoint(
            $request->user(),
            $request->validated('endpoint'),
        );

        return ApiResponse::success(null, 'Push subscription removed.');
    }
}
