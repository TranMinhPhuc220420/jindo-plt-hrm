<?php

namespace App\Models;

use Database\Factories\ShiftAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftAssignment extends Model
{
    /** @use HasFactory<ShiftAssignmentFactory> */
    use HasFactory, SoftDeletes;

    public const SOURCE_RECURRING = 'recurring';

    public const SOURCE_ADHOC = 'adhoc';

    public const SOURCES = [
        self::SOURCE_RECURRING,
        self::SOURCE_ADHOC,
    ];

    protected $fillable = [
        'company_id',
        'employee_id',
        'shift_id',
        'start_date',
        'end_date',
        'weekdays',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'weekdays' => 'array',
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
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
