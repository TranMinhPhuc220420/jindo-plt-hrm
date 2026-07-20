<?php

namespace App\Services\Leave;

use App\Models\WeekendRule;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;

class WeekendRuleService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    public function get(): WeekendRule
    {
        $companyId = $this->companyContext->id();

        return WeekendRule::query()->firstOrCreate(
            ['company_id' => $companyId],
            ['weekend_days' => [0, 6]],
        );
    }

    /**
     * @param  array{weekend_days: list<int>}  $data
     */
    public function upsert(array $data): WeekendRule
    {
        $companyId = $this->companyContext->id();
        $days = array_values(array_unique(array_map('intval', $data['weekend_days'])));

        $rule = WeekendRule::query()->updateOrCreate(
            ['company_id' => $companyId],
            ['weekend_days' => $days],
        );

        $this->audit->write(
            action: 'leave.weekend_rules_updated',
            subject: $rule,
            payload: ['weekend_days' => $days],
        );

        return $rule->fresh();
    }
}
