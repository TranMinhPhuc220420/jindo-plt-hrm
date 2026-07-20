<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Company;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->where('code', 'JINDO')->first();

        if ($company === null) {
            return;
        }

        Asset::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'LAPTOP-0001',
            ],
            [
                'name' => 'Dell Latitude 5540',
                'category' => 'laptop',
                'status' => 'available',
                'serial_number' => 'DL5540-DEMO-0001',
                'assigned_to' => null,
                'notes' => 'Demo laptop seeded for local development.',
            ],
        );
    }
}
