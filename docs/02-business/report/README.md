# Report

> Aggregated reporting across HR domains; feeds dashboards later.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Reports

---

## Purpose

Provide read-oriented insights over domain data. Reports depend on domain modules; domain modules must not depend on Reports to complete operational writes.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Attendance Reports | Presence, late, overtime, summaries |
| Payroll Reports | Runs, costs, components |
| Leave Reports | Requests, balances, utilization |
| Employee Reports | Headcount, status, org distribution |
| Department Reports | Department-scoped aggregations |
| Performance Reports | Review outcomes, goal progress |
| Custom Reports | Configurable/saved report definitions (as product allows) |

Dashboard (later phase) consumes report/read models.

---

## Business Rules

1. Reporting is primarily **read** — it must not become the write path for attendance, leave, or payroll.
2. Authorization is mandatory; salary/payroll reports are highly sensitive.
3. Prefer querying via domain services or approved read models/projections — not ad-hoc joins that bypass module contracts when those contracts exist.
4. Heavy exports run asynchronously (queue) with a status/download flow.
5. Company scope applies to all report queries.
6. Custom reports cannot escalate privileges beyond the requester’s permissions.
7. Numbers in reports should reconcile with domain sources of truth; if caching/projections are used, define refresh rules.

---

## Key Workflows

### Run standard report

```
User selects report type + filters
  → Authorize → Query/read model
    → Return tabular/chart payload
```

### Export

```
Request export
  → Enqueue job → Generate file (CSV/PDF)
    → Store temporarily → Notify + download link
```

### Custom report (when enabled)

```
Define columns/filters within allowed dataset
  → Save definition → Run with same permission checks
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Employee, Attendance, Leave, Shift, Payroll, Performance, Assets, Documents (read) | Being required by those modules for their writes |
| Authorization | Circular event loops with domain modules |
| File storage for exports | |
| Notifications for export-ready alerts | |

Dependency direction:

```
Domain modules → Reports → Dashboard
```

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_attendance_reports` | Attendance reporting |
| `can_view_payroll_reports` | Payroll reporting |
| `can_view_leave_reports` | Leave reporting |
| `can_view_employee_reports` | Employee/HR reporting |
| `can_view_performance_reports` | Performance reporting |
| `can_manage_custom_reports` | Create/manage custom definitions |
| `can_export_reports` | Export/download |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `ReportExportRequested` | Queue generation job |
| `ReportExportReady` | Notify requester |
| Domain projections (optional) | Update read models after domain events — without owning domain writes |

---

## Out of Scope / Future

- Full analytics warehouse / BI suite
- AI assistant over reports
- Real-time cross-tenant SaaS analytics

---

## Related Documents

- [../attendance/](../attendance/)
- [../payroll/](../payroll/)
- [../leave/](../leave/)
- [../employee/](../employee/)
- [../performance/](../performance/)
- [../../01-architecture/DEPENDENCY_RULES.md](../../01-architecture/DEPENDENCY_RULES.md)
- `docs/06-api/REPORT_API.md`
