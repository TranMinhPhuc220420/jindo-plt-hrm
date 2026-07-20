<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'probation',
        'active',
        'suspended',
        'resigned',
        'archived',
    ];

    protected $fillable = [
        'company_id',
        'code',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'branch_id',
        'department_id',
        'team_id',
        'position_id',
        'manager_id',
        'supervisor_id',
        'hr_owner_id',
        'user_id',
        'hired_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
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
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<EmployeeEmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    /**
     * @return HasMany<EmployeeEducation, $this>
     */
    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    /**
     * @return HasMany<EmployeeWorkHistory, $this>
     */
    public function workHistories(): HasMany
    {
        return $this->hasMany(EmployeeWorkHistory::class);
    }

    /**
     * @return HasMany<EmployeeFamilyMember, $this>
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }

    /**
     * @return HasMany<EmployeeContract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    /**
     * @return HasOne<EmployeeBankAccount, $this>
     */
    public function bankAccount(): HasOne
    {
        return $this->hasOne(EmployeeBankAccount::class);
    }

    /**
     * @return HasOne<EmployeeInsurance, $this>
     */
    public function insurance(): HasOne
    {
        return $this->hasOne(EmployeeInsurance::class);
    }

    /**
     * @return HasOne<EmployeeTaxProfile, $this>
     */
    public function taxProfile(): HasOne
    {
        return $this->hasOne(EmployeeTaxProfile::class);
    }

    /**
     * @return HasMany<ShiftAssignment, $this>
     */
    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * @return HasMany<AttendanceCorrection, $this>
     */
    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
