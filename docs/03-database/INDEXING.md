# Indexing

> Index strategy for HRM MySQL tables.
>
> Conventions: [DATABASE_CONVENTIONS.md](./DATABASE_CONVENTIONS.md) · Naming: [DATABASE_NAMING.md](./DATABASE_NAMING.md)

---

## Goals

1. Keep common list/filter/queue queries fast under company scope.
2. Support date-range scans for attendance, leave, and payroll.
3. Enforce uniqueness that matches multi-company business rules.
4. Avoid over-indexing write-heavy tables without evidence.

---

## Default Indexes

Create indexes for:

| Pattern | Example |
|---------|---------|
| Every FK used in joins/filters | `company_id`, `employee_id`, `leave_type_id` |
| Status queue columns | `status`, often composite with `company_id` |
| Soft deletes when queried | `(company_id, deleted_at)` or include `deleted_at` in common composites as needed |
| Polymorphic pairs | `(subject_type, subject_id)` |

Primary keys are already indexed.

---

## Required Unique Constraints (typical)

| Table | Unique | Reason |
|-------|--------|--------|
| `employees` | `(company_id, code)` | Employee code per company |
| `permissions` | `(name)` or `(key)` | Global permission keys |
| `roles` | `(company_id, name)` or global per product rule | Role identity |
| `leave_balances` | `(employee_id, leave_type_id, period_key)` | One balance row per period |
| `holidays` | `(company_id, date)` | One holiday definition per date |
| `shift_assignments` | Controlled overlap via app + supportive indexes | Prevent double assignment windows |
| `payroll_runs` | `(company_id, period_start, period_end, run_type)` as designed | Avoid duplicate runs |
| `users` | `email` (or login identifier) | Auth identity |

Adjust exact unique sets when product rules differ — document deviations in migrations.

---

## High-Value Composite Indexes

### Attendance

| Index | Supports |
|-------|----------|
| `(company_id, employee_id, work_date)` | Daily attendance lookup |
| `(company_id, status, work_date)` | Approval queues |
| `(employee_id, work_date)` | Employee history |

### Leave

| Index | Supports |
|-------|----------|
| `(company_id, status, start_date)` | Approval queues |
| `(employee_id, status, start_date)` | My leave history |
| `(leave_type_id, employee_id)` | Balance joins |

### Shift

| Index | Supports |
|-------|----------|
| `(employee_id, start_date, end_date)` | Assignment resolution |
| `(company_id, shift_id)` | Shift usage |

### Payroll

| Index | Supports |
|-------|----------|
| `(company_id, period_start, period_end)` | Run lookup |
| `(payroll_run_id, employee_id)` | Items in a run (unique often) |
| `(employee_id, created_at)` | Payslip history |

### Employee / Org

| Index | Supports |
|-------|----------|
| `(company_id, status)` | Headcount lists |
| `(company_id, department_id, status)` | Department lists |
| `(manager_id)` | Direct reports |

### Audit / Notifications

| Index | Supports |
|-------|----------|
| `audit_logs (company_id, created_at)` | Audit browsing |
| `audit_logs (subject_type, subject_id, created_at)` | Subject history |
| `notifications (user_id, read_at, created_at)` | Inbox |

### Documents / Assets

| Index | Supports |
|-------|----------|
| `documents (company_id, owner_type, owner_id)` | Owner files |
| `asset_assignments (asset_id, returned_at)` | Active custody |
| `asset_assignments (employee_id, returned_at)` | Employee assets |

---

## Covering / Extra Columns

Add included/extra columns only when measured beneficial. Prefer selective composites matching real `WHERE` + `ORDER BY` patterns from APIs and reports.

---

## What Not to Index Blindly

1. Low-selectivity flags alone (`is_active`) without company/status companions
2. Wide text/JSON columns
3. Every column “just in case”
4. Duplicate indexes that left-prefix the same composite
5. Write-hot logs with excessive unique constraints

---

## Migration Practice for Indexes

- Add indexes in the same migration as new FKs when creating tables.
- For large existing tables, prefer separate index migrations and maintenance windows.
- Name indexes per [DATABASE_NAMING.md](./DATABASE_NAMING.md).
- Verify with `EXPLAIN` on critical queries before/after major changes.

---

## Reporting Workloads

Heavy report filters may need:

- Extra composites on date + department + status
- Read models / summary tables (owned carefully — not a second write path for domain facts)
- Async export rather than blocking OLTP with huge sorts

See [../02-business/report/README.md](../02-business/report/README.md).

---

## Checklist

Before merging a schema change:

- [ ] FKs indexed
- [ ] Company-scoped uniques defined where needed
- [ ] Approval queue filters have a matching composite
- [ ] Employee + date-range access path exists for time/money tables
- [ ] No redundant indexes introduced

---

## Related Documents

- [DATABASE_NAMING.md](./DATABASE_NAMING.md)
- [MIGRATION_RULES.md](./MIGRATION_RULES.md)
- [../01-architecture/DATABASE_ARCHITECTURE.md](../01-architecture/DATABASE_ARCHITECTURE.md)
