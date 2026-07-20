<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PerformanceGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceGoal>
 */
class PerformanceGoalFactory extends Factory
{
    protected $model = PerformanceGoal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'review_cycle_id' => null,
            'employee_id' => Employee::factory(),
            'title' => 'Ship Q3 roadmap',
            'description' => null,
            'type' => 'okr',
            'metric' => null,
            'target' => null,
            'weight' => null,
            'progress' => 0,
            'status' => 'active',
            'created_by' => null,
        ];
    }
}
