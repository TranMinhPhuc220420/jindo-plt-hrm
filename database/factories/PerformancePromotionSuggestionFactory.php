<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PerformancePromotionSuggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformancePromotionSuggestion>
 */
class PerformancePromotionSuggestionFactory extends Factory
{
    protected $model = PerformancePromotionSuggestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'review_cycle_id' => null,
            'evaluation_id' => null,
            'overall_score' => 4.6,
            'status' => 'suggested',
            'note' => null,
            'suggested_at' => now(),
            'acknowledged_by' => null,
            'acknowledged_at' => null,
        ];
    }
}
