<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OnboardingTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingTemplate>
 */
class OnboardingTemplateFactory extends Factory
{
    protected $model = OnboardingTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Standard onboarding '.$this->faker->unique()->numberBetween(1, 99999),
            'description' => 'Default onboarding checklist',
            'is_active' => true,
        ];
    }
}
