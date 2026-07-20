<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'ANN-'.fake()->unique()->numerify('###'),
            'name' => 'Annual Leave',
            'unit_default' => 'day',
            'is_paid' => true,
            'requires_balance' => true,
            'allows_negative' => false,
            'is_active' => true,
        ];
    }
}
