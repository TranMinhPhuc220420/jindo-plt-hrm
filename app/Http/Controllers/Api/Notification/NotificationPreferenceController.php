<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationPreferenceResource;
use App\Services\Notification\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorize('can_view_own_notifications');

        $prefs = $this->notifications->preferencesFor($request->user());

        return ApiResponse::success(
            (new NotificationPreferenceResource($prefs))->resolve(),
        );
    }

    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $prefs = $this->notifications->updatePreferences($request->user(), $request->validated());

        return ApiResponse::success(
            (new NotificationPreferenceResource($prefs))->resolve(),
            'Notification preferences updated.',
        );
    }
}
