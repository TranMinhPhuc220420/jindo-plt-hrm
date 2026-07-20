<?php

namespace App\Models;

use Database\Factories\OvertimeRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRule extends Model
{
    /** @use HasFactory<OvertimeRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'applies_after_minutes',
        'allow_before_shift',
        'night_ot_enabled',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applies_after_minutes' => 'integer',
            'allow_before_shift' => 'boolean',
            'night_ot_enabled' => 'boolean',
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
}
