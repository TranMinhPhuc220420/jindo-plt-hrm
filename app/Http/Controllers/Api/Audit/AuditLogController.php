<?php

namespace App\Http\Controllers\Api\Audit;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Services\Audit\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('can_view_audit_logs');

        $paginator = $this->audit->list(
            filters: $request->only([
                'action',
                'actor_id',
                'subject_type',
                'subject_id',
                'date_from',
                'date_to',
            ]),
            perPage: (int) $request->integer('per_page', 20),
        );

        return ApiResponse::paginated(
            $paginator->through(fn ($log) => (new AuditLogResource($log))->resolve()),
        );
    }

    public function show(int $auditLog): JsonResponse
    {
        $this->authorize('can_view_audit_logs');

        $log = $this->audit->find($auditLog);

        return ApiResponse::success(
            (new AuditLogResource($log))->resolve(),
        );
    }
}
