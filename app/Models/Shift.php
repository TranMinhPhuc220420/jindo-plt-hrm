<?php

namespace App\Models;

use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory, SoftDeletes;

    public const KINDS = [
        'standard',
        'rotating',
        'night',
        'flexible',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'start_time',
        'end_time',
        'break_minutes',
        'kind',
        'is_night',
        'is_flexible',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'is_night' => 'boolean',
            'is_flexible' => 'boolean',
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
     * @return HasMany<ShiftAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }
}
