<?php

namespace App\Services\Report;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\PayrollRun;
use App\Models\Setting;
use App\Models\User;
use App\Services\Organization\CompanyContext;
use App\Support\SettingsDefaults;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    private const DEPT_TOP_N = 5;

    private const LIST_LIMIT = 6;

    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(User $actor): array
    {
        if ($actor->can('can_view_employee_reports')) {
            return $this->companySummary($actor);
        }

        return $this->selfSummary($actor);
    }

    /**
     * @return array<string, mixed>
     */
    private function companySummary(User $actor): array
    {
        $companyId = $this->companyContext->id();
        $tz = $this->companyTimezone();
        $today = Carbon::now($tz)->startOfDay();
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();
        $todayDate = $today->toDateString();

        $activeEmployees = Employee::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        $presentToday = $this->presentCountForDate($companyId, $todayDate);
        $attendanceTodayRate = $activeEmployees > 0
            ? round($presentToday / $activeEmployees, 4)
            : 0.0;

        $pendingLeave = LeaveRequest::query()
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->count();

        $pendingCorrections = AttendanceCorrection::query()
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->count();

        $openPayroll = PayrollRun::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['draft', 'calculated', 'approved'])
            ->count();

        $unread = $this->unreadCount($actor);

        $newHiresMonth = Employee::query()
            ->where('company_id', $companyId)
            ->whereBetween('hired_at', [$monthStart, $monthEnd])
            ->count();

        return [
            'scope' => 'company',
            'active_employees' => $activeEmployees,
            'attendance_today_rate' => $attendanceTodayRate,
            'pending_leave_requests' => $pendingLeave,
            'new_hires_month' => $newHiresMonth,
            'open_payroll_runs' => $openPayroll,
            'unread_notifications' => $unread,
            'attendance_last_7_days' => $this->attendanceLast7Days($companyId, $today, $activeEmployees),
            'employees_by_status' => $this->employeesByStatus($companyId),
            'employees_by_department' => $this->employeesByDepartment($companyId),
            'recent_hires' => $this->recentHires($companyId),
            'pending_actions' => $this->pendingActions($pendingLeave, $pendingCorrections, $openPayroll),
            'upcoming' => $this->upcomingCompany($companyId, $today),
            'recent_activity' => $this->recentActivity($actor),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function selfSummary(User $actor): array
    {
        $companyId = $this->companyContext->id();
        $tz = $this->companyTimezone();
        $today = Carbon::now($tz)->startOfDay();
        $todayDate = $today->toDateString();
        $year = (string) $today->year;

        $employee = Employee::query()
            ->with('department:id,name')
            ->where('company_id', $companyId)
            ->where('user_id', $actor->id)
            ->first();

        $unread = $this->unreadCount($actor);

        if ($employee === null) {
            return [
                'scope' => 'self',
                'employee' => null,
                'unread_notifications' => $unread,
                'today_attendance' => null,
                'checked_in_today' => false,
                'pending_leave_requests' => 0,
                'leave_balances' => [],
                'my_attendance_last_7_days' => [],
                'upcoming' => $this->upcomingHolidays($companyId, $today),
                'pending_actions' => $this->selfPendingActions(0, $unread),
                'recent_activity' => $this->recentActivity($actor),
            ];
        }

        $todayRecord = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $todayDate)
            ->orderByDesc('id')
            ->first();

        $pendingLeave = LeaveRequest::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $pendingCorrections = AttendanceCorrection::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        return [
            'scope' => 'self',
            'employee' => [
                'id' => $employee->id,
                'code' => $employee->code,
                'full_name' => $employee->full_name,
                'department_name' => $employee->department?->name,
                'status' => $employee->status,
            ],
            'unread_notifications' => $unread,
            'today_attendance' => $todayRecord === null ? null : [
                'id' => $todayRecord->id,
                'work_date' => $todayRecord->work_date?->toDateString(),
                'check_in_at' => $todayRecord->check_in_at?->toIso8601String(),
                'check_out_at' => $todayRecord->check_out_at?->toIso8601String(),
                'worked_minutes' => $todayRecord->worked_minutes,
                'status' => $todayRecord->status,
            ],
            'checked_in_today' => $todayRecord !== null && $todayRecord->check_in_at !== null,
            'pending_leave_requests' => $pendingLeave,
            'leave_balances' => $this->leaveBalances($companyId, $employee->id, $year),
            'my_attendance_last_7_days' => $this->myAttendanceLast7Days($companyId, $employee->id, $today),
            'upcoming' => $this->upcomingSelf($companyId, $employee->id, $today),
            'pending_actions' => $this->selfPendingActions($pendingLeave, $unread, $pendingCorrections),
            'recent_activity' => $this->recentActivity($actor),
        ];
    }

    /**
     * @return list<array{date: string, label: string, present: int, expected: int, rate: float}>
     */
    private function attendanceLast7Days(int $companyId, Carbon $today, int $expected): array
    {
        $start = $today->copy()->subDays(6);
        $dates = [];

        for ($d = $start->copy(); $d->lte($today); $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        $presentByDate = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->whereIn('work_date', $dates)
            ->whereNotNull('check_in_at')
            ->selectRaw('work_date, COUNT(DISTINCT employee_id) as present')
            ->groupBy('work_date')
            ->pluck('present', 'work_date');

        $series = [];

        foreach ($dates as $date) {
            $present = (int) ($presentByDate[$date] ?? 0);
            $rate = $expected > 0 ? round($present / $expected, 4) : 0.0;
            $series[] = [
                'date' => $date,
                'label' => Carbon::parse($date, $today->timezone)->format('D'),
                'present' => $present,
                'expected' => $expected,
                'rate' => $rate,
            ];
        }

        return $series;
    }

    /**
     * @return list<array{date: string, label: string, present: int, worked_minutes: int|null}>
     */
    private function myAttendanceLast7Days(int $companyId, int $employeeId, Carbon $today): array
    {
        $start = $today->copy()->subDays(6);
        $dates = [];

        for ($d = $start->copy(); $d->lte($today); $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        $records = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereIn('work_date', $dates)
            ->whereNotNull('check_in_at')
            ->get()
            ->keyBy(fn (AttendanceRecord $r) => $r->work_date->toDateString());

        $series = [];

        foreach ($dates as $date) {
            /** @var AttendanceRecord|null $record */
            $record = $records->get($date);
            $series[] = [
                'date' => $date,
                'label' => Carbon::parse($date, $today->timezone)->format('D'),
                'present' => $record !== null ? 1 : 0,
                'worked_minutes' => $record?->worked_minutes,
            ];
        }

        return $series;
    }

    /**
     * @return list<array{leave_type_id: int, leave_type_code: string, leave_type_name: string, remaining: float, entitled: float, used: float, pending: float}>
     */
    private function leaveBalances(int $companyId, int $employeeId, string $year): array
    {
        return array_values(LeaveBalance::query()
            ->with('leaveType:id,code,name')
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('period_key', $year)
            ->orderBy('leave_type_id')
            ->get()
            ->map(fn (LeaveBalance $b) => [
                'leave_type_id' => $b->leave_type_id,
                'leave_type_code' => $b->leaveType->code ?? '',
                'leave_type_name' => $b->leaveType->name ?? '',
                'remaining' => $b->remaining(),
                'entitled' => (float) $b->entitled,
                'used' => (float) $b->used,
                'pending' => (float) $b->pending,
            ])
            ->all());
    }

    /**
     * @return list<array{status: string, count: int}>
     */
    private function employeesByStatus(int $companyId): array
    {
        return array_values(Employee::query()
            ->where('company_id', $companyId)
            ->toBase()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(fn (object $row) => [
                'status' => (string) $row->status,
                'count' => (int) $row->count,
            ])
            ->all());
    }

    /**
     * @return list<array{department_id: int|null, name: string, count: int}>
     */
    private function employeesByDepartment(int $companyId): array
    {
        $rows = Employee::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->toBase()
            ->selectRaw('department_id, COUNT(*) as count')
            ->groupBy('department_id')
            ->orderByDesc('count')
            ->get();

        $deptIds = $rows->pluck('department_id')->filter()->unique()->values();
        $names = Department::query()
            ->whereIn('id', $deptIds)
            ->pluck('name', 'id');

        $top = $rows->take(self::DEPT_TOP_N);
        $rest = $rows->slice(self::DEPT_TOP_N);
        $result = [];

        foreach ($top as $row) {
            $deptId = $row->department_id !== null ? (int) $row->department_id : null;
            $result[] = [
                'department_id' => $deptId,
                'name' => $deptId !== null
                    ? (string) ($names[$deptId] ?? '—')
                    : 'Unassigned',
                'count' => (int) $row->count,
            ];
        }

        $otherCount = (int) $rest->sum('count');

        if ($otherCount > 0) {
            $result[] = [
                'department_id' => null,
                'name' => 'Other',
                'count' => $otherCount,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{id: int, code: string, full_name: string, department_name: string|null, hired_at: string|null, status: string}>
     */
    private function recentHires(int $companyId): array
    {
        return array_values(Employee::query()
            ->with('department:id,name')
            ->where('company_id', $companyId)
            ->whereNotNull('hired_at')
            ->orderByDesc('hired_at')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'code' => $e->code,
                'full_name' => $e->full_name,
                'department_name' => $e->department?->name,
                'hired_at' => $e->hired_at?->toDateString(),
                'status' => $e->status,
            ])
            ->all());
    }

    /**
     * @return list<array{key: string, count: int, href: string}>
     */
    private function pendingActions(int $pendingLeave, int $pendingCorrections, int $openPayroll): array
    {
        $actions = [];

        if ($pendingLeave > 0) {
            $actions[] = [
                'key' => 'pending_leave',
                'count' => $pendingLeave,
                'href' => '/leave',
            ];
        }

        if ($pendingCorrections > 0) {
            $actions[] = [
                'key' => 'pending_corrections',
                'count' => $pendingCorrections,
                'href' => '/attendance/corrections',
            ];
        }

        if ($openPayroll > 0) {
            $actions[] = [
                'key' => 'open_payroll',
                'count' => $openPayroll,
                'href' => '/payroll',
            ];
        }

        return $actions;
    }

    /**
     * @return list<array{key: string, count: int, href: string}>
     */
    private function selfPendingActions(int $pendingLeave, int $unread, int $pendingCorrections = 0): array
    {
        $actions = [];

        if ($pendingLeave > 0) {
            $actions[] = [
                'key' => 'my_pending_leave',
                'count' => $pendingLeave,
                'href' => '/leave',
            ];
        }

        if ($pendingCorrections > 0) {
            $actions[] = [
                'key' => 'my_pending_corrections',
                'count' => $pendingCorrections,
                'href' => '/attendance/corrections',
            ];
        }

        if ($unread > 0) {
            $actions[] = [
                'key' => 'unread_notifications',
                'count' => $unread,
                'href' => '/notifications',
            ];
        }

        return $actions;
    }

    /**
     * @return list<array{kind: string, date: string, title: string, employee_name?: string|null}>
     */
    private function upcomingCompany(int $companyId, Carbon $today): array
    {
        $end = $today->copy()->addDays(14)->toDateString();
        $todayDate = $today->toDateString();

        $holidays = $this->holidayItems($companyId, $todayDate, $end);

        $leaves = LeaveRequest::query()
            ->with('employee:id,full_name')
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereBetween('start_date', [$todayDate, $end])
            ->orderBy('start_date')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (LeaveRequest $lr) => [
                'kind' => 'leave',
                'date' => $lr->start_date->toDateString(),
                'title' => $lr->employee?->full_name ?? 'Leave',
                'employee_name' => $lr->employee?->full_name,
            ]);

        /** @var Collection<int, array{kind: string, date: string, title: string, employee_name?: string|null}> $merged */
        $merged = collect($holidays)->concat($leaves)
            ->sortBy('date')
            ->take(self::LIST_LIMIT)
            ->values();

        return array_values($merged->all());
    }

    /**
     * @return list<array{kind: string, date: string, title: string, employee_name?: string|null}>
     */
    private function upcomingSelf(int $companyId, int $employeeId, Carbon $today): array
    {
        $end = $today->copy()->addDays(14)->toDateString();
        $todayDate = $today->toDateString();

        $holidays = $this->holidayItems($companyId, $todayDate, $end);

        $leaves = LeaveRequest::query()
            ->with('leaveType:id,name')
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($q) use ($todayDate, $end): void {
                $q->whereBetween('start_date', [$todayDate, $end])
                    ->orWhere(function ($inner) use ($todayDate): void {
                        $inner->where('start_date', '<=', $todayDate)
                            ->where('end_date', '>=', $todayDate);
                    });
            })
            ->orderBy('start_date')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (LeaveRequest $lr) => [
                'kind' => 'leave',
                'date' => $lr->start_date->toDateString(),
                'title' => $lr->leaveType->name ?? 'Leave',
                'employee_name' => null,
            ]);

        /** @var Collection<int, array{kind: string, date: string, title: string, employee_name?: string|null}> $merged */
        $merged = collect($holidays)->concat($leaves)
            ->sortBy('date')
            ->take(self::LIST_LIMIT)
            ->values();

        return array_values($merged->all());
    }

    /**
     * @return list<array{kind: string, date: string, title: string}>
     */
    private function upcomingHolidays(int $companyId, Carbon $today): array
    {
        $end = $today->copy()->addDays(14)->toDateString();

        return $this->holidayItems($companyId, $today->toDateString(), $end);
    }

    /**
     * @return list<array{kind: string, date: string, title: string}>
     */
    private function holidayItems(int $companyId, string $from, string $to): array
    {
        return array_values(Holiday::query()
            ->where('company_id', $companyId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (Holiday $h) => [
                'kind' => 'holiday',
                'date' => $h->date->toDateString(),
                'title' => $h->name,
            ])
            ->all());
    }

    /**
     * @return list<array{id: int, type: string, title: string, body: string|null, created_at: string, read_at: string|null}>
     */
    private function recentActivity(User $actor): array
    {
        return array_values(Notification::query()
            ->where('user_id', $actor->id)
            ->orderByDesc('created_at')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (Notification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'created_at' => $n->created_at?->toIso8601String() ?? '',
                'read_at' => $n->read_at?->toIso8601String(),
            ])
            ->all());
    }

    private function unreadCount(User $actor): int
    {
        return Notification::query()
            ->where('user_id', $actor->id)
            ->whereNull('read_at')
            ->count();
    }

    private function presentCountForDate(int $companyId, string $date): int
    {
        return (int) AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->whereDate('work_date', $date)
            ->whereNotNull('check_in_at')
            ->selectRaw('COUNT(DISTINCT employee_id) as aggregate')
            ->value('aggregate');
    }

    private function companyTimezone(): string
    {
        $stored = Setting::query()
            ->where('company_id', $this->companyContext->id())
            ->where('group', 'company')
            ->where('key', 'timezone')
            ->value('value');

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return (string) (SettingsDefaults::all()['company']['timezone'] ?? 'UTC');
    }
}
