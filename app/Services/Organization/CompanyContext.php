<?php

namespace App\Services\Organization;

use App\Exceptions\DomainException;
use App\Models\Company;

/**
 * Resolves the active company for v1 (single-company runtime, multi-company-ready schema).
 */
class CompanyContext
{
    public function current(): Company
    {
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
