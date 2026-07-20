<?php

use App\Services\Payroll\Strategies\MonthlyPayrollStrategy;

function baseInput(array $overrides = []): array
{
    return array_merge([
        'employee_id' => 1,
        'period_start' => '2026-07-01',
        'period_end' => '2026-07-31',
        'base_amount' => 10_000_000,
        'currency' => 'VND',
        'allowances' => [],
        'bonuses' => [],
        'deductions' => [],
        'overtime_minutes' => 0,
        'unpaid_leave_days' => 0,
    ], $overrides);
}

test('base salary only produces a single salary component', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput());

    expect($result['gross'])->toBe(10_000_000.0);
    expect($result['net'])->toBe(10_000_000.0);
    expect($result['components'])->toBe([
        ['type' => 'salary', 'label' => 'Base', 'amount' => '10000000.00'],
    ]);
});

test('allowances and bonuses increase gross and net', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput([
        'allowances' => [['code' => 'transport', 'name' => 'Transport', 'amount' => 500_000]],
        'bonuses' => [['code' => 'kpi', 'name' => 'Performance', 'amount' => 1_000_000]],
    ]));

    expect($result['gross'])->toBe(11_500_000.0);
    expect($result['net'])->toBe(11_500_000.0);
    expect($result['components'])->toBe([
        ['type' => 'salary', 'label' => 'Base', 'amount' => '10000000.00'],
        ['type' => 'allowance', 'label' => 'Transport', 'amount' => '500000.00'],
        ['type' => 'bonus', 'label' => 'Performance', 'amount' => '1000000.00'],
    ]);
});

test('deductions reduce net but not gross', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput([
        'deductions' => [['code' => 'tax', 'name' => 'Tax', 'amount' => 800_000]],
    ]));

    expect($result['gross'])->toBe(10_000_000.0);
    expect($result['net'])->toBe(9_200_000.0);
    expect($result['components'])->toContain(
        ['type' => 'deduction', 'label' => 'Tax', 'amount' => '-800000.00'],
    );
});

test('overtime pays 1.5x the hourly rate derived from 176 monthly hours', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput([
        'base_amount' => 17_600_000,
        'overtime_minutes' => 120,
    ]));

    // hourly = 17,600,000 / 176 = 100,000; OT = 2h * 100,000 * 1.5 = 300,000
    expect($result['gross'])->toBe(17_900_000.0);
    expect($result['net'])->toBe(17_900_000.0);
    expect($result['components'])->toContain(
        ['type' => 'overtime', 'label' => 'OT', 'amount' => '300000.00'],
    );
});

test('overtime is ignored when base salary is zero', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput([
        'base_amount' => 0,
        'overtime_minutes' => 120,
    ]));

    expect($result['gross'])->toBe(0.0);
    expect($result['components'])->toHaveCount(1);
});

test('unpaid leave deducts a prorated day rate based on 30 days per month', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput([
        'base_amount' => 9_000_000,
        'unpaid_leave_days' => 2,
    ]));

    // day rate = 9,000,000 / 30 = 300,000; 2 unpaid days = 600,000
    expect($result['gross'])->toBe(9_000_000.0);
    expect($result['net'])->toBe(8_400_000.0);
    expect($result['components'])->toContain(
        ['type' => 'deduction', 'label' => 'Unpaid leave', 'amount' => '-600000.00'],
    );
});

test('unpaid leave is ignored when base salary is zero', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput([
        'base_amount' => 0,
        'unpaid_leave_days' => 3,
    ]));

    expect($result['net'])->toBe(0.0);
    expect($result['components'])->toHaveCount(1);
});

test('amounts round to two decimal places', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput([
        'base_amount' => 100.126,
        'allowances' => [['code' => 'misc', 'name' => 'Misc', 'amount' => 50.004]],
    ]));

    expect($result['gross'])->toBe(150.13);
    expect($result['components'])->toBe([
        ['type' => 'salary', 'label' => 'Base', 'amount' => '100.13'],
        ['type' => 'allowance', 'label' => 'Misc', 'amount' => '50.00'],
    ]);
});

test('full scenario combines allowances, bonuses, overtime, deductions, and unpaid leave in order', function () {
    $result = (new MonthlyPayrollStrategy)->calculate(baseInput([
        'base_amount' => 17_600_000,
        'allowances' => [
            ['code' => 'transport', 'name' => 'Transport', 'amount' => 500_000],
            ['code' => 'meal', 'name' => 'Meal', 'amount' => 300_000],
        ],
        'bonuses' => [['code' => 'kpi', 'name' => 'KPI', 'amount' => 1_000_000]],
        'overtime_minutes' => 60,
        'deductions' => [
            ['code' => 'tax', 'name' => 'Tax', 'amount' => 2_000_000],
            ['code' => 'insurance', 'name' => 'Insurance', 'amount' => 500_000],
        ],
        'unpaid_leave_days' => 1,
    ]));

    expect($result['gross'])->toBe(19_550_000.0);
    expect($result['net'])->toBe(16_463_333.33);
    expect($result['components'])->toBe([
        ['type' => 'salary', 'label' => 'Base', 'amount' => '17600000.00'],
        ['type' => 'allowance', 'label' => 'Transport', 'amount' => '500000.00'],
        ['type' => 'allowance', 'label' => 'Meal', 'amount' => '300000.00'],
        ['type' => 'bonus', 'label' => 'KPI', 'amount' => '1000000.00'],
        ['type' => 'overtime', 'label' => 'OT', 'amount' => '150000.00'],
        ['type' => 'deduction', 'label' => 'Tax', 'amount' => '-2000000.00'],
        ['type' => 'deduction', 'label' => 'Insurance', 'amount' => '-500000.00'],
        ['type' => 'deduction', 'label' => 'Unpaid leave', 'amount' => '-586666.67'],
    ]);
});
