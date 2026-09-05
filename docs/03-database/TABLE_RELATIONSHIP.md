# Table Relationship

> Ownership, cardinality, and FK rules between modules.
>
> ERD diagrams: [ERD.md](./ERD.md) · Naming: [DATABASE_NAMING.md](./DATABASE_NAMING.md)

---

## Relationship Principles

1. **Identity hubs** (`companies`, `users`, `employees`) are widely referenced.
2. **Module-owned satellites** cascade/soft-delete with their owner according to documented rules.
3. **No circular ownership** between Attendance and Payroll, or Reports and domain write tables.
4. **Cardinality must match business reality** (one tax profile vs many educations).
5. **Cross-module reads** go through services even when FKs exist.

---

## Module → Table Ownership

| Module | Owns (logical) |
|--------|----------------|
| Auth | `users` (+ auth satellite tables) |
| Authorization | `roles`, `permissions`, `role_user`, `permission_role` |
| Organization | `companies`, `branches`, `departments`, `teams`, `positions` |
| Employee | `employees` + `employee_*` satellites |
| Attendance | `attendance_*` |
| Leave | `leave_*`, `holidays`, `weekend_rules` |
| Shift | `shifts`, `shift_assignments`, `overtime_rules` |
| Payroll | salary/allowance/deduction/bonus, `payroll_runs`, `payroll_items`, `payslips` |
| Recruitment | `job_openings`, `candidates`, `interviews`, `candidate_evaluations`, `offers` |
| Onboarding | `onboarding_cases`, `onboarding_tasks` |
| Performance | `performance_*` |
| Assets | `assets`, `asset_*` |
| Documents | `documents` |
| Notifications | `notifications` (+ delivery tables) |
| System | `audit_logs`, `settings` |

---

## Core Cardinalities

### Organization & Employee

| Parent | Child | Cardinality | Notes |
|--------|-------|-------------|-------|
| `companies` | `branches` | 1:N | |
| `companies` | `employees` | 1:N | Required scope |
| `departments` | `employees` | 1:N | Nullable only if product allows unassigned |
| `positions` | `employees` | 1:N | Org position, not recruitment opening |
| `employees` | `employees` | 1:N | Manager/supervisor self-FK |
| `users` | `employees` | 1:0..1 | Link after account provisioning |
| `employees` | `employee_educations` | 1:N | |
| `employees` | `employee_tax_profiles` | 1:0..1 | Prefer single current profile |

### Time

| Parent | Child | Cardinality | Notes |
|--------|-------|-------------|-------|
| `employees` | `attendance_records` | 1:N | Unique per `(work_date, shift_id)` |
| `shifts` | `attendance_records` | 1:N | Punch session belongs to a shift |
| `attendance_records` | `attendance_corrections` | 1:N | |
| `employees` | `shift_assignments` | 1:N | Date range + optional weekday mask |
| `shifts` | `shift_assignments` | 1:N | |
| `leave_types` | `leave_balances` | 1:N | Per employee + period |
| `employees` | `leave_requests` | 1:N | |

### Payroll

| Parent | Child | Cardinality | Notes |
|--------|-------|-------------|-------|
| `employees` | `employee_salaries` | 1:N | Effective-dated versions recommended |
| `payroll_runs` | `payroll_items` | 1:N | |
| `payroll_items` | `payslips` | 1:0..1 | After finalize |
| `employees` | `payslips` | 1:N | History |

### Hire path

| Parent | Child | Cardinality | Notes |
|--------|-------|-------------|-------|
| `job_openings` | `candidates` | 1:N | |
| `candidates` | `offers` | 1:N | |
| `offers` | `onboarding_cases` | 1:0..1 | On accept |
| `onboarding_cases` | `onboarding_tasks` | 1:N | |
| `onboarding_cases` | `employees` | 1:0..1 | Activation link |

### Assets & Documents

| Parent | Child | Cardinality | Notes |
|--------|-------|-------------|-------|
| `companies` | `assets` | 1:N | |
| `assets` | `asset_assignments` | 1:N | History; one active assignment by default |
| `employees` | `asset_assignments` | 1:N | |
| `companies` / `employees` | `documents` | 1:N | Owner pattern |

---

## Foreign Key Policy

| Situation | Policy |
|-----------|--------|
| Child cannot exist without parent | FK required |
| Historical reference after parent soft-delete | Keep FK; soft-delete parent |
| Optional link | Nullable FK |
| Polymorphic owner (documents, audit subject) | `*_type` + `*_id` + index; app-enforced integrity |
| Cross-module reference | FK to identity table OK; do not FK into another module’s deep satellites unless necessary |

---

## Delete Behaviors (guidance)

| Case | Suggested behavior |
|------|--------------------|
| Delete draft leave type with no usage | Hard delete or soft delete |
| Delete employee | Soft delete / archive status — never hard delete if payroll/attendance history exists |
| Delete company (future SaaS) | Restricted; operationally deactivate |
| Delete payroll run | Forbidden after finalize |
| Delete asset with assignment history | Soft delete / retire status |
| Delete document metadata | Soft delete + storage retention policy |

---

## Relationship Anti-Patterns

1. Payroll item FK pointing at attendance punch as ownership parent of payslip
2. Dual-writing employee status from Leave and Employee without a single owner
3. Embedding org hierarchy only as free-text on employees with no FK path
4. Shared “misc” table used by every module without ownership
5. Report tables that applications write as the system of record for attendance/leave

---

## Service Boundary Reminder

Even with FKs present:

```
PayrollService → AttendanceService.getSummary(employee, period)
```

not

```
PayrollRepository → join attendance_records → mutate payslips from random controllers
```

See [DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md).

---

## Related Documents

- [ERD.md](./ERD.md)
- [DATABASE_CONVENTIONS.md](./DATABASE_CONVENTIONS.md)
- [INDEXING.md](./INDEXING.md)
