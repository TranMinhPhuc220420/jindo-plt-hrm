<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OnboardingCase;
use App\Models\OnboardingTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingTask>
 */
class OnboardingTaskFactory extends Factory
{
    protected $model = OnboardingTask::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'onboarding_case_id' => OnboardingCase::factory(),
            'key' => 'task_'.$this->faker->unique()->numberBetween(1, 99999),
            'title' => $this->faker->sentence(3),
            'description' => null,
            'mandatory' => false,
            'assignee_type' => 'hr',
            'status' => 'pending',
            'sort_order' => 0,
            'completed_at' => null,
            'completed_by' => null,
        ];
    }
}
