<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PerformanceReviewCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceReviewCycle>
 */
class PerformanceReviewCycleFactory extends Factory
{
    protected $model = PerformanceReviewCycle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'H2 2026 Review',
            'framework' => 'okr',
            'status' => 'draft',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-12-31',
            'participant_employee_ids' => [],
            'started_at' => null,
            'finalized_at' => null,
            'created_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'started_at' => now(),
        ]);
    }
}
