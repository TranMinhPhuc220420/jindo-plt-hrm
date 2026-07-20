<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\BroadcastNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\Notification\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('can_view_own_notifications');

        $paginator = $this->notifications->list(
            user: $request->user(),
            filters: [
                'unread_only' => $request->boolean('unread_only'),
                'type' => $request->query('type'),
            ],
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (Notification $row) => (new NotificationResource($row))->resolve()),
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $this->authorize('can_view_own_notifications');

        return ApiResponse::success([
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function read(Request $request, int $notification): JsonResponse
    {
        $this->authorize('can_view_own_notifications');

        $model = $this->notifications->markRead($request->user(), $notification);

        return ApiResponse::success(
            (new NotificationResource($model))->resolve(),
            'Notification marked as read.',
        );
    }

    public function readAll(Request $request): JsonResponse
    {
        $this->authorize('can_view_own_notifications');

        $count = $this->notifications->markAllRead($request->user());

        return ApiResponse::success(['marked' => $count], 'All notifications marked as read.');
    }

    public function destroy(Request $request, int $notification): JsonResponse
    {
        $this->authorize('can_view_own_notifications');

        $this->notifications->delete($request->user(), $notification);

        return ApiResponse::success(null, 'Notification deleted.');
    }

    public function broadcast(BroadcastNotificationRequest $request): JsonResponse
    {
        $count = $this->notifications->broadcast(
            title: $request->validated('title'),
            body: $request->validated('body'),
        );

        return ApiResponse::success(
            ['sent' => $count],
            'Broadcast notification queued.',
        );
    }
}
