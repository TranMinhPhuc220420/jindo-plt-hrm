<?php

namespace App\Services\Payroll\Strategies;

use App\Services\Payroll\Contracts\PayrollCalculationStrategy;

class MonthlyPayrollStrategy implements PayrollCalculationStrategy
{
    private const HOURS_PER_MONTH = 176.0;

    private const OT_MULTIPLIER = 1.5;

    private const DAYS_PER_MONTH = 30.0;

    public function calculate(array $input): array
    {
        $base = round((float) $input['base_amount'], 2);
        $components = [
            [
                'type' => 'salary',
                'label' => 'Base',
                'amount' => $this->format($base),
            ],
        ];

        $gross = $base;

        foreach ($input['allowances'] as $row) {
            $amount = round((float) $row['amount'], 2);
            $gross += $amount;
            $components[] = [
                'type' => 'allowance',
                'label' => $row['name'],
                'amount' => $this->format($amount),
            ];
        }

        foreach ($input['bonuses'] as $row) {
            $amount = round((float) $row['amount'], 2);
            $gross += $amount;
            $components[] = [
                'type' => 'bonus',
                'label' => $row['name'],
                'amount' => $this->format($amount),
            ];
        }

        $otMinutes = (int) $input['overtime_minutes'];
        if ($otMinutes > 0 && $base > 0) {
            $hourly = $base / self::HOURS_PER_MONTH;
            $otAmount = round(($otMinutes / 60) * $hourly * self::OT_MULTIPLIER, 2);
            $gross += $otAmount;
            $components[] = [
                'type' => 'overtime',
                'label' => 'OT',
                'amount' => $this->format($otAmount),
            ];
        }

        $deductionTotal = 0.0;

        foreach ($input['deductions'] as $row) {
            $amount = round((float) $row['amount'], 2);
            $deductionTotal += $amount;
            $components[] = [
                'type' => 'deduction',
                'label' => $row['name'],
                'amount' => $this->format(-$amount),
            ];
        }

        $unpaidDays = (float) $input['unpaid_leave_days'];
        if ($unpaidDays > 0 && $base > 0) {
            $unpaidAmount = round($unpaidDays * ($base / self::DAYS_PER_MONTH), 2);
            $deductionTotal += $unpaidAmount;
            $components[] = [
                'type' => 'deduction',
                'label' => 'Unpaid leave',
                'amount' => $this->format(-$unpaidAmount),
            ];
        }

        $net = round($gross - $deductionTotal, 2);

        return [
            'gross' => round($gross, 2),
            'net' => $net,
            'components' => $components,
        ];
    }

    private function format(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
