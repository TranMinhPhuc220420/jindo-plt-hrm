<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OvertimeRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeRule>
 */
class OvertimeRuleFactory extends Factory
{
    protected $model = OvertimeRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'OT-'.fake()->unique()->numerify('###'),
            'name' => 'Standard overtime',
            'applies_after_minutes' => 0,
            'allow_before_shift' => false,
            'night_ot_enabled' => false,
            'is_active' => true,
        ];
    }
}
