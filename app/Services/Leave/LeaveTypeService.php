<?php

namespace App\Services\Leave;

use App\Exceptions\DomainException;
use App\Models\LeaveType;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaveTypeService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string, is_active?: bool|string}  $filters
     * @return LengthAwarePaginator<int, LeaveType>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = LeaveType::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('name');

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search);
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): LeaveType
    {
        $type = LeaveType::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($type === null) {
            throw new DomainException(
                message: 'Leave type not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $type;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeaveType
    {
        $companyId = $this->companyContext->id();

        $exists = LeaveType::query()
            ->where('company_id', $companyId)
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            throw new DomainException(
                message: 'Leave type code already exists.',
                errorCode: 'VALIDATION_FAILED',
                status: 422,
            );
        }

        $type = LeaveType::query()->create([
            'company_id' => $companyId,
            'code' => $data['code'],
            'name' => $data['name'],
            'unit_default' => $data['unit_default'] ?? 'day',
            'is_paid' => $data['is_paid'] ?? true,
            'requires_balance' => $data['requires_balance'] ?? true,
            'allows_negative' => $data['allows_negative'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->write(
            action: 'leave.type_created',
            subject: $type,
            payload: ['code' => $type->code],
        );

        return $type;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LeaveType $type, array $data): LeaveType
    {
        if (isset($data['code']) && $data['code'] !== $type->code) {
            $exists = LeaveType::query()
                ->where('company_id', $type->company_id)
                ->where('code', $data['code'])
                ->where('id', '!=', $type->id)
                ->exists();

            if ($exists) {
                throw new DomainException(
                    message: 'Leave type code already exists.',
                    errorCode: 'VALIDATION_FAILED',
                    status: 422,
                );
            }
        }

        $type->fill($data);
        $type->save();

        $this->audit->write(
            action: 'leave.type_updated',
            subject: $type,
            payload: ['code' => $type->code],
        );

        return $type->fresh();
    }
}
