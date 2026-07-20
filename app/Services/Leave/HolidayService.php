<?php

namespace App\Services\Leave;

use App\Exceptions\DomainException;
use App\Models\Holiday;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Support\Collection;

class HolidayService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, Holiday>
     */
    public function list(?string $year = null): Collection
    {
        $query = Holiday::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('date');

        if ($year !== null && $year !== '') {
            $query->whereYear('date', (int) $year);
        }

        return $query->get();
    }

    public function find(int $id): Holiday
    {
        $holiday = Holiday::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($holiday === null) {
            throw new DomainException(
                message: 'Holiday not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $holiday;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Holiday
    {
        $companyId = $this->companyContext->id();

        $exists = Holiday::query()
            ->where('company_id', $companyId)
            ->whereDate('date', $data['date'])
            ->exists();

        if ($exists) {
            throw new DomainException(
                message: 'A holiday already exists for this date.',
                errorCode: 'VALIDATION_FAILED',
                status: 422,
            );
        }

        $holiday = Holiday::query()->create([
            'company_id' => $companyId,
            'date' => $data['date'],
            'name' => $data['name'],
        ]);

        $this->audit->write(
            action: 'leave.holiday_created',
            subject: $holiday,
            payload: ['date' => $data['date']],
        );

        return $holiday;
    }

    public function delete(Holiday $holiday): void
    {
        $date = $holiday->date->toDateString();
        $holiday->delete();

        $this->audit->write(
            action: 'leave.holiday_deleted',
            subject: null,
            payload: ['date' => $date, 'id' => $holiday->id],
        );
    }
}
