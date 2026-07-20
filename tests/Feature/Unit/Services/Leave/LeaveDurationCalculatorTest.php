<?php

use App\Models\Company;
use App\Models\Holiday;
use App\Models\WeekendRule;
use App\Services\Leave\LeaveDurationCalculator;
use Carbon\CarbonImmutable;

function calculator(): LeaveDurationCalculator
{
    return app(LeaveDurationCalculator::class);
}

test('day unit counts every calendar day when there is no weekend rule or holiday', function () {
    Company::factory()->create();

    $start = CarbonImmutable::parse('2026-01-01')->next(1); // a Monday
    $end = $start->addDays(2); // Mon-Wed, no weekend involved

    $quantity = calculator()->calculate([
        'unit' => 'day',
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
    ]);

    expect($quantity)->toBe(3.0);
});

test('day unit excludes weekend days using the default Sat/Sun rule', function () {
    Company::factory()->create();

    $friday = CarbonImmutable::parse('2026-01-01')->next(5); // a Friday
    $monday = $friday->next(1); // the following Monday

    $quantity = calculator()->calculate([
        'unit' => 'day',
        'start_date' => $friday->toDateString(),
        'end_date' => $monday->toDateString(),
    ]);

    expect($quantity)->toBe(2.0); // Friday + Monday, Sat/Sun excluded
});

test('day unit respects a custom weekend rule', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create([
        'company_id' => $company->id,
        'weekend_days' => [5, 6], // Friday & Saturday as weekend
    ]);

    $friday = CarbonImmutable::parse('2026-01-01')->next(5);
    $sunday = $friday->addDays(2); // Fri, Sat, Sun

    $quantity = calculator()->calculate([
        'unit' => 'day',
        'start_date' => $friday->toDateString(),
        'end_date' => $sunday->toDateString(),
    ]);

    expect($quantity)->toBe(1.0); // only Sunday counts
});

test('day unit excludes company holidays within the range', function () {
    $company = Company::factory()->create();
    $monday = CarbonImmutable::parse('2026-01-01')->next(1);

    Holiday::factory()->create([
        'company_id' => $company->id,
        'date' => $monday->addDay()->toDateString(), // Tuesday is a holiday
    ]);

    $quantity = calculator()->calculate([
        'unit' => 'day',
        'start_date' => $monday->toDateString(),
        'end_date' => $monday->addDays(2)->toDateString(), // Mon-Wed
    ]);

    expect($quantity)->toBe(2.0); // Tuesday excluded
});

test('a single day range counts as one day', function () {
    Company::factory()->create();
    $monday = CarbonImmutable::parse('2026-01-01')->next(1);

    $quantity = calculator()->calculate([
        'unit' => 'day',
        'start_date' => $monday->toDateString(),
        'end_date' => $monday->toDateString(),
    ]);

    expect($quantity)->toBe(1.0);
});

test('half_day unit always returns 0.5 regardless of range', function () {
    Company::factory()->create();

    $quantity = calculator()->calculate([
        'unit' => 'half_day',
        'start_date' => '2026-01-05',
        'end_date' => '2026-01-05',
    ]);

    expect($quantity)->toBe(0.5);
});

test('is_half_day flag overrides the unit and returns 0.5', function () {
    Company::factory()->create();

    $quantity = calculator()->calculate([
        'unit' => 'day',
        'start_date' => '2026-01-05',
        'end_date' => '2026-01-07',
        'is_half_day' => true,
    ]);

    expect($quantity)->toBe(0.5);
});

test('hour unit computes duration directly from start_at/end_at', function () {
    Company::factory()->create();

    $quantity = calculator()->calculate([
        'unit' => 'hour',
        'start_date' => '2026-01-05',
        'end_date' => '2026-01-05',
        'start_at' => '2026-01-05 09:00:00',
        'end_at' => '2026-01-05 12:30:00',
    ]);

    expect($quantity)->toBe(3.5);
});
