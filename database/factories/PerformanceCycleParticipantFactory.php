<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PerformanceCycleParticipant;
use App\Models\PerformanceReviewCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceCycleParticipant>
 */
class PerformanceCycleParticipantFactory extends Factory
{
    protected $model = PerformanceCycleParticipant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'review_cycle_id' => PerformanceReviewCycle::factory(),
            'employee_id' => Employee::factory(),
        ];
    }
}
