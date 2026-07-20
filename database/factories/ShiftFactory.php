<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Morning',
            'code' => 'MOR-'.fake()->unique()->numerify('###'),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'break_minutes' => 60,
            'kind' => 'standard',
            'is_night' => false,
            'is_flexible' => false,
            'is_active' => true,
        ];
    }
}
