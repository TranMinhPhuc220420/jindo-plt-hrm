<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string|null $school
 * @property string|null $degree
 * @property string|null $field_of_study
 * @property CarbonImmutable|null $started_on
 * @property CarbonImmutable|null $ended_on
 * @property string|null $notes
 */
class EmployeeEducation extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'school',
        'degree',
        'field_of_study',
        'started_on',
        'ended_on',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
