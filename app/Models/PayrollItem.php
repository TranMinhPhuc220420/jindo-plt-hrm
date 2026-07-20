<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollItem extends Model
{
    /** @use HasFactory<\Database\Factories\PayrollItemFactory> */
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'company_id',
        'employee_id',
        'gross',
        'net',
        'components',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
            'net' => 'decimal:2',
            'components' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasOne<Payslip, $this>
     */
    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }
}
