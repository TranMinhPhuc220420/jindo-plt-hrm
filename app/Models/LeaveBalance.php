<?php

namespace App\Models;

use Database\Factories\LeaveBalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    /** @use HasFactory<LeaveBalanceFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'period_key',
        'entitled',
        'used',
        'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entitled' => 'decimal:2',
            'used' => 'decimal:2',
            'pending' => 'decimal:2',
        ];
    }

    public function remaining(): float
    {
        return (float) $this->entitled - (float) $this->used - (float) $this->pending;
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
