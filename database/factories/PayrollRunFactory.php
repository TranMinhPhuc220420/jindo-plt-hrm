<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRun>
 */
class PayrollRunFactory extends Factory
{
    protected $model = PayrollRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'July 2026',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'run_type' => 'regular',
            'status' => 'draft',
            'employee_count' => 0,
            'total_gross' => 0,
            'total_net' => 0,
        ];
    }
}
