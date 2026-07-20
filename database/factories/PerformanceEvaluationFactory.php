<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PerformanceEvaluation;
use App\Models\PerformanceReviewCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceEvaluation>
 */
class PerformanceEvaluationFactory extends Factory
{
    protected $model = PerformanceEvaluation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'review_cycle_id' => PerformanceReviewCycle::factory(),
            'employee_id' => Employee::factory(),
            'evaluator_id' => null,
            'overall_score' => 4.0,
            'summary' => 'Strong delivery',
            'ratings' => [],
            'submitted_at' => now(),
        ];
    }
}
