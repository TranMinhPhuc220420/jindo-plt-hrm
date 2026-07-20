<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Organization\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function write(
        string $action,
        ?Model $subject = null,
        ?array $payload = null,
        ?User $actor = null,
        ?int $companyId = null,
    ): AuditLog {
        $actor ??= Auth::user();

        try {
            $companyId ??= $this->companyContext->id();
        } catch (\Throwable) {
            $companyId = null;
        }

        return AuditLog::query()->create([
            'company_id' => $companyId,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'payload' => $payload,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array{action?: string, actor_id?: int, subject_type?: string, subject_id?: int, date_from?: string, date_to?: string}  $filters
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id'])
                ->where('actor_type', (new User)->getMorphClass());
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): AuditLog
    {
        return AuditLog::query()
            ->where('company_id', $this->companyContext->id())
            ->findOrFail($id);
    }
}
