<?php

namespace App\Services\Payroll\Contracts;

interface PayrollCalculationStrategy
{
    /**
     * @param  array{
     *     employee_id: int,
     *     period_start: string,
     *     period_end: string,
     *     base_amount: float,
     *     currency: string,
     *     allowances: list<array{code: string, name: string, amount: float}>,
     *     bonuses: list<array{code: string, name: string, amount: float}>,
     *     deductions: list<array{code: string, name: string, amount: float}>,
     *     overtime_minutes: int,
     *     unpaid_leave_days: float
     * }  $input
     * @return array{
     *     gross: float,
     *     net: float,
     *     components: list<array{type: string, label: string, amount: string}>
     * }
     */
    public function calculate(array $input): array;
}
