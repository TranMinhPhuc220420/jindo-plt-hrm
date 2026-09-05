<?php

namespace App\Services\Organization;

use App\Exceptions\DomainException;
use App\Models\Company;

/**
 * Resolves the active company for v1 (single-company runtime, multi-company-ready schema).
 */
class CompanyContext
{
    private ?int $forcedId = null;

    /**
     * Run a callback with a fixed company (CLI / scheduler). Nested calls restore the previous override.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function using(int $companyId, callable $callback): mixed
    {
        $previous = $this->forcedId;
        $this->forcedId = $companyId;

        try {
            return $callback();
        } finally {
            $this->forcedId = $previous;
        }
    }

    public function current(): Company
    {
        if ($this->forcedId !== null) {
            $forced = Company::query()->whereKey($this->forcedId)->where('is_active', true)->first();

            if ($forced) {
                return $forced;
            }
        }

        $companyId = session('company_id');

        if ($companyId) {
            $company = Company::query()->whereKey($companyId)->where('is_active', true)->first();

            if ($company) {
                return $company;
            }
        }

        $company = Company::query()->where('is_active', true)->orderBy('id')->first();

        if (! $company) {
            throw new DomainException(
                message: 'No active company is configured.',
                errorCode: 'COMPANY_NOT_CONFIGURED',
                status: 404,
            );
        }

        session(['company_id' => $company->id]);

        return $company;
    }

    public function id(): int
    {
        return $this->current()->id;
    }
}
