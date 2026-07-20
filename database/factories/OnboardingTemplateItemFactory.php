<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OnboardingTemplate;
use App\Models\OnboardingTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingTemplateItem>
 */
class OnboardingTemplateItemFactory extends Factory
{
    protected $model = OnboardingTemplateItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'onboarding_template_id' => OnboardingTemplate::factory(),
            'key' => 'task_'.$this->faker->unique()->numberBetween(1, 99999),
            'title' => $this->faker->sentence(3),
            'description' => null,
            'mandatory' => false,
            'assignee_type' => 'hr',
            'sort_order' => 0,
        ];
    }
}
