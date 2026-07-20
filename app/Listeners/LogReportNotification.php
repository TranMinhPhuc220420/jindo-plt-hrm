<?php

namespace App\Listeners;

use App\Events\ReportExportReady;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class LogReportNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handleExportReady(ReportExportReady $event): void
    {
        $export = $event->export;
        Log::info('report.notification', [
            'type' => 'report.export_ready',
            'report_export_id' => $export->id,
            'user_id' => $export->user_id,
        ]);

        $export->loadMissing('user');
        if ($export->user === null) {
            return;
        }

        $this->notifications->notify(
            user: $export->user,
            type: 'report.export_ready',
            data: [
                'report_export_id' => $export->id,
                'report' => $export->report,
            ],
            companyId: $export->company_id,
        );
    }
}
