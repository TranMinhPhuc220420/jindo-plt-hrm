<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OnboardingCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingCase>
 */
class OnboardingCaseFactory extends Factory
{
    protected $model = OnboardingCase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'offer_id' => null,
            'candidate_id' => null,
            'onboarding_template_id' => null,
            'status' => 'in_progress',
            'probation_ends_on' => '2026-11-01',
            'started_at' => now(),
            'completed_at' => null,
        ];
    }
}
