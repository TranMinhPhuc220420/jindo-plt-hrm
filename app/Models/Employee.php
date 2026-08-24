<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'probation',
        'active',
        'suspended',
        'resigned',
        'archived',
    ];

    /** @var list<string> */
    public const LOGIN_BLOCKED_STATUSES = [
        'suspended',
        'resigned',
        'archived',
    ];

    /** @var list<string> */
    public const PUNCH_ALLOWED_STATUSES = [
        'probation',
        'active',
    ];

    /** @var list<string> */
    public const OFFBOARDING_STATUSES = [
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
        'terminated_at',
        'status',
        'avatar_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
            'terminated_at' => 'date',
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

    /**
     * @return HasMany<AssetAssignment, $this>
     */
    public function activeAssetAssignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class)
            ->where('status', 'active')
            ->whereNull('returned_at');
    }

    public function isLoginBlocked(): bool
    {
        return in_array($this->status, self::LOGIN_BLOCKED_STATUSES, true);
    }

    public function canPunch(): bool
    {
        return in_array($this->status, self::PUNCH_ALLOWED_STATUSES, true);
    }

    public static function isOffboardingStatus(string $status): bool
    {
        return in_array($status, self::OFFBOARDING_STATUSES, true);
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null || $this->avatar_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }
}
