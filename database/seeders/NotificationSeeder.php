<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * @var list<array{type: string, read: bool}>
     */
    private const ITEMS = [
        [
            'type' => 'leave.approved',
            'read' => false,
        ],
        [
            'type' => 'payroll.finalized',
            'read' => false,
        ],
        [
            'type' => 'onboarding.started',
            'read' => true,
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $user = User::query()->where('email', 'admin@example.test')->first();

        if ($user === null) {
            return;
        }

        $companyId = Company::query()->where('code', 'JINDO')->value('id');
        $locale = $user->locale ?: config('app.locale', 'vi');

        foreach (self::ITEMS as $item) {
            $type = $item['type'];
            $title = __("notifications.{$type}.title", [], $locale);
            $body = __("notifications.{$type}.body", [], $locale);

            Notification::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $type,
                ],
                [
                    'company_id' => $companyId,
                    'title' => $title === "notifications.{$type}.title" ? $type : $title,
                    'body' => $body === "notifications.{$type}.body" ? null : $body,
                    'data' => null,
                    'read_at' => $item['read'] ? now() : null,
                ],
            );
        }
    }
}
