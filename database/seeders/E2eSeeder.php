<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Deterministic dataset for Playwright / CI browser smoke.
 * Reuses the full local demo seed (admin@example.test / password).
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->call(DatabaseSeeder::class);
    }
}
