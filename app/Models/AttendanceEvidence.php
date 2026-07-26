<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEvidence extends Model
{
    protected $table = 'attendance_evidences';

    public const PUNCH_CHECK_IN = 'check_in';

    public const PUNCH_CHECK_OUT = 'check_out';

    public const PUNCH_TYPES = [
        self::PUNCH_CHECK_IN,
        self::PUNCH_CHECK_OUT,
    ];

    protected $fillable = [
        'company_id',
        'attendance_record_id',
        'punch_type',
        'latitude',
        'longitude',
        'accuracy_meters',
        'address',
        'photo_path',
        'photo_mime',
        'photo_size',
        'captured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_meters' => 'float',
            'photo_size' => 'integer',
            'captured_at' => 'datetime',
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
     * @return BelongsTo<AttendanceRecord, $this>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }
}
