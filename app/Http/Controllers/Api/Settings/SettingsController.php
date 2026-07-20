<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Services\Settings\SettingsService;
use App\Support\ApiResponse;
use App\Support\SettingsDefaults;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('can_view_settings');

        return ApiResponse::success($this->settings->all());
    }

    public function show(string $group): JsonResponse
    {
        $this->authorize('can_view_settings');

        return ApiResponse::success($this->settings->all($group));
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->authorize('can_manage_settings');

        $payload = collect($request->all())
            ->only(SettingsDefaults::allowedGroups())
            ->all();

        $data = $this->settings->update($payload);

        return ApiResponse::success($data, 'Settings updated.');
    }
}
