<?php

namespace Database\Seeders\Concerns;

use App\Models\OvertimeRule;
use App\Models\Shift;

trait SeedsShiftDefinitions
{
    /**
     * Upsert standard shift definitions and the STANDARD overtime rule for a company.
     * Does not create shift assignments.
     */
    protected function seedShiftDefinitions(int $companyId): void
    {
        Shift::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'MORNING'],
            [
                'name' => 'Morning',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_minutes' => 60,
                'kind' => 'standard',
                'is_night' => false,
                'is_flexible' => false,
                'is_active' => true,
            ],
        );

        Shift::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'NIGHT'],
            [
                'name' => 'Night',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'break_minutes' => 45,
                'kind' => 'night',
                'is_night' => true,
                'is_flexible' => false,
                'is_active' => true,
            ],
        );

        Shift::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'MORNING_PT'],
            [
                'name' => 'Morning part-time',
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'break_minutes' => 0,
                'kind' => 'standard',
                'is_night' => false,
                'is_flexible' => false,
                'is_active' => true,
            ],
        );

        Shift::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'AFTERNOON_PT'],
            [
                'name' => 'Afternoon part-time',
                'start_time' => '13:00:00',
                'end_time' => '17:00:00',
                'break_minutes' => 0,
                'kind' => 'standard',
                'is_night' => false,
                'is_flexible' => false,
                'is_active' => true,
            ],
        );

        OvertimeRule::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'STANDARD'],
            [
                'name' => 'Standard overtime',
                'applies_after_minutes' => 0,
                'allow_before_shift' => false,
                'night_ot_enabled' => true,
                'is_active' => true,
            ],
        );
    }
}
