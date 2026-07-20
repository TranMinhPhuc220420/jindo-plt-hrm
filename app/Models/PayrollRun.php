<?php

namespace App\Models;

use Database\Factories\PayrollRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    /** @use HasFactory<PayrollRunFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'calculated', 'approved', 'finalized'];

    protected $fillable = [
        'company_id',
        'name',
        'period_start',
        'period_end',
        'run_type',
        'status',
        'employee_count',
        'total_gross',
        'total_net',
        'calculated_at',
        'approved_at',
        'finalized_at',
        'calculated_by',
        'approved_by',
        'finalized_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'employee_count' => 'integer',
            'total_gross' => 'decimal:2',
            'total_net' => 'decimal:2',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<PayrollItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
