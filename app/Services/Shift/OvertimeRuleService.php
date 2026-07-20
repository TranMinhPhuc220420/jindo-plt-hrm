<?php

namespace App\Services\Shift;

use App\Models\OvertimeRule;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OvertimeRuleService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, OvertimeRule>
     */
    public function list(): Collection
    {
        return OvertimeRule::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('code')
            ->get();
    }

    /**
     * Replace-set company overtime rules.
     *
     * @param  list<array{
     *     code: string,
     *     name: string,
     *     applies_after_minutes?: int,
     *     allow_before_shift?: bool,
     *     night_ot_enabled?: bool,
     *     is_active?: bool
     * }>  $rules
     * @return Collection<int, OvertimeRule>
     */
    public function replace(array $rules): Collection
    {
        $companyId = $this->companyContext->id();

        return DB::transaction(function () use ($companyId, $rules) {
            OvertimeRule::query()->where('company_id', $companyId)->delete();

            $created = collect($rules)->map(function (array $rule) use ($companyId): OvertimeRule {
                return OvertimeRule::query()->create([
                    'company_id' => $companyId,
                    'code' => $rule['code'],
                    'name' => $rule['name'],
                    'applies_after_minutes' => $rule['applies_after_minutes'] ?? 0,
                    'allow_before_shift' => $rule['allow_before_shift'] ?? false,
                    'night_ot_enabled' => $rule['night_ot_enabled'] ?? false,
                    'is_active' => $rule['is_active'] ?? true,
                ]);
            });

            $this->audit->write(
                action: 'overtime_rules.updated',
                subject: null,
                payload: [
                    'count' => $created->count(),
                    'codes' => $created->pluck('code')->all(),
                ],
            );

            return OvertimeRule::query()
                ->where('company_id', $companyId)
                ->orderBy('code')
                ->get();
        });
    }
}
