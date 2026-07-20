<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PerformanceCycleParticipant;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReviewCycle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PerformanceSeeder extends Seeder
{
    /**
     * @var list<array{title: string, type: string, metric: string, target: string, progress: int}>
     */
    private const GOALS = [
        [
            'title' => 'Ship the HRM insight module',
            'type' => 'goal',
            'metric' => 'Delivery',
            'target' => 'Phase 08 shipped',
            'progress' => 40,
        ],
        [
            'title' => 'Maintain code review SLA',
            'type' => 'kpi',
            'metric' => 'Median review time',
            'target' => '< 4 hours',
            'progress' => 60,
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->where('code', 'JINDO')->first();

        if ($company === null) {
            return;
        }

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('code', 'E-0001')
            ->first();

        if ($employee === null) {
            return;
        }

        $cycle = PerformanceReviewCycle::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Annual review '.CarbonImmutable::now()->year,
            ],
            [
                'framework' => 'goal',
                'status' => 'active',
                'starts_on' => CarbonImmutable::now()->startOfYear()->toDateString(),
                'ends_on' => CarbonImmutable::now()->endOfYear()->toDateString(),
                'participant_employee_ids' => [$employee->id],
                'started_at' => now(),
                'finalized_at' => null,
            ],
        );

        PerformanceCycleParticipant::query()->updateOrCreate(
            [
                'review_cycle_id' => $cycle->id,
                'employee_id' => $employee->id,
            ],
            [
                'company_id' => $company->id,
            ],
        );

        foreach (self::GOALS as $goal) {
            PerformanceGoal::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'review_cycle_id' => $cycle->id,
                    'employee_id' => $employee->id,
                    'title' => $goal['title'],
                ],
                [
                    'type' => $goal['type'],
                    'metric' => $goal['metric'],
                    'target' => $goal['target'],
                    'weight' => null,
                    'progress' => $goal['progress'],
                    'status' => 'active',
                ],
            );
        }
    }
}
