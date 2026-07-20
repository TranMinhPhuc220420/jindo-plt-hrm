<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportExport>
 */
class ReportExportFactory extends Factory
{
    protected $model = ReportExport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'report' => 'attendance',
            'format' => 'csv',
            'filters' => [],
            'status' => 'pending',
            'path' => null,
            'error_message' => null,
        ];
    }
}
