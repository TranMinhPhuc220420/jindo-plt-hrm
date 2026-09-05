<?php

namespace App\Services\Shift;

use App\Exceptions\DomainException;
use App\Models\Shift;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;

class ShiftService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string, kind?: string, is_active?: bool|string}  $filters
     * @return LengthAwarePaginator<int, Shift>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Shift::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('name');

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search);
            });
        }

        if (! empty($filters['kind'])) {
            $query->where('kind', $filters['kind']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '') {
            $active = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($active !== null) {
                $query->where('is_active', $active);
            }
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Shift
    {
        return Shift::query()
            ->where('company_id', $this->companyContext->id())
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Shift
    {
        $companyId = $this->companyContext->id();
        $this->assertValidTimeRange($data);

        $data['company_id'] = $companyId;
        $data['break_minutes'] = $data['break_minutes'] ?? 0;
        $data['kind'] = $data['kind'] ?? 'standard';
        $data['is_night'] = $data['is_night'] ?? ($data['kind'] === 'night');
        $data['is_flexible'] = $data['is_flexible'] ?? ($data['kind'] === 'flexible');
        $data['is_active'] = $data['is_active'] ?? true;
        $data['start_time'] = $this->normalizeTime($data['start_time']);
        $data['end_time'] = $this->normalizeTime($data['end_time']);

        try {
            $shift = Shift::query()->create($data);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new DomainException(
                    message: 'Shift code already exists for this company.',
                    errorCode: 'SHIFT_CODE_DUPLICATE',
                    status: 422,
                );
            }

            throw $e;
        }

        $this->audit->write(
            action: 'shift.created',
            subject: $shift,
            payload: ['code' => $shift->code, 'kind' => $shift->kind],
        );

        return $shift;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Shift $shift, array $data): Shift
    {
        $this->assertCompanyScope($shift->company_id);

        $merged = array_merge($shift->only([
            'start_time',
            'end_time',
            'is_night',
            'kind',
        ]), $data);

        $this->assertValidTimeRange($merged);

        if (isset($data['start_time'])) {
            $data['start_time'] = $this->normalizeTime($data['start_time']);
        }

        if (isset($data['end_time'])) {
            $data['end_time'] = $this->normalizeTime($data['end_time']);
        }

        try {
            $shift->update($data);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new DomainException(
                    message: 'Shift code already exists for this company.',
                    errorCode: 'SHIFT_CODE_DUPLICATE',
                    status: 422,
                );
            }

            throw $e;
        }

        $this->audit->write(
            action: 'shift.updated',
            subject: $shift,
            payload: ['code' => $shift->code],
        );

        return $shift->fresh();
    }

    public function delete(Shift $shift): void
    {
        $this->assertCompanyScope($shift->company_id);

        $inUse = $shift->assignments()
            ->whereNull('deleted_at')
            ->where(function ($q): void {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->exists();

        if ($inUse) {
            throw new DomainException(
                message: 'Cannot delete a shift that still has active assignments.',
                errorCode: 'SHIFT_IN_USE',
                status: 422,
            );
        }

        $shift->delete();

        $this->audit->write(
            action: 'shift.deleted',
            subject: $shift,
            payload: ['code' => $shift->code],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertValidTimeRange(array $data): void
    {
        $start = $this->normalizeTime((string) ($data['start_time'] ?? ''));
        $end = $this->normalizeTime((string) ($data['end_time'] ?? ''));
        $isNight = (bool) ($data['is_night'] ?? false) || (($data['kind'] ?? '') === 'night');

        if ($start === '' || $end === '') {
            throw new DomainException(
                message: 'Shift start and end time are required.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }

        if ($start === $end) {
            throw new DomainException(
                message: 'Shift start and end time must differ.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }

        if ($end < $start && ! $isNight) {
            throw new DomainException(
                message: 'End time must be after start time unless the shift is night.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);

        if (preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            return $time.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time) === 1) {
            return $time;
        }

        return $time;
    }

    private function assertCompanyScope(int $companyId): void
    {
        if ($companyId !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Resource does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'uq_shifts_company_id_code')
            || str_contains($message, 'UNIQUE constraint failed')
            || (string) $e->getCode() === '23000';
    }
}
