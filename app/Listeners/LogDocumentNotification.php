<?php

namespace App\Listeners;

use App\Events\DocumentUploaded;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class LogDocumentNotification
{
    private const SENSITIVE_CATEGORIES = ['contract', 'certificate'];

    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handleUploaded(DocumentUploaded $event): void
    {
        $document = $event->document;
        Log::info('document.notification', [
            'type' => 'document.uploaded',
            'document_id' => $document->id,
            'owner_type' => $document->owner_type,
            'category' => $document->category,
        ]);

        $data = [
            'document_id' => $document->id,
            'category' => $document->category,
        ];

        if ($document->owner_type === 'employee' && $document->owner_id !== null) {
            $this->notifications->notifyEmployee(
                employeeId: $document->owner_id,
                type: 'document.shared',
                data: $data,
                companyId: $document->company_id,
            );
        }

        if (in_array($document->category, self::SENSITIVE_CATEGORIES, true)) {
            foreach ($this->recipients->usersWithPermissionInCompany(
                $document->company_id,
                'can_view_employee_documents',
            ) as $hr) {
                $this->notifications->notify(
                    user: $hr,
                    type: 'document.uploaded',
                    data: $data,
                    companyId: $document->company_id,
                );
            }
        }
    }
}
