<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveTypeFactory> */
    use HasFactory;

    public const UNITS = ['day', 'half_day', 'hour'];

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'unit_default',
        'is_paid',
        'requires_balance',
        'allows_negative',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'requires_balance' => 'boolean',
            'allows_negative' => 'boolean',
            'is_active' => 'boolean',
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
     * @return HasMany<LeaveBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
