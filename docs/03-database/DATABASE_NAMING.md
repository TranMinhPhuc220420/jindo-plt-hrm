# Database Naming

> Naming conventions for MySQL tables, columns, indexes, and constraints.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)
>
> Architecture: [DATABASE_ARCHITECTURE.md](../01-architecture/DATABASE_ARCHITECTURE.md)

---

## General Rules

| Item | Convention | Example |
|------|------------|---------|
| Language | English | `employees`, not `nhan_vien` |
| Case | `snake_case` | `leave_requests` |
| Tables | Plural nouns | `departments` |
| Columns | Singular nouns / clear phrases | `hired_at`, `company_id` |
| Boolean columns | `is_` / `has_` prefix | `is_active`, `has_probation` |
| Datetime columns | `*_at` | `approved_at`, `created_at` |
| Date-only columns | `*_date` or domain name | `birth_date`, `start_date` |
| Soft delete | `deleted_at` | Laravel convention |
| Pivot tables | Singular parts, alphabetical when no clear owner | `permission_role`, `employee_shift` |

Avoid reserved MySQL words as bare names (`order`, `group`, `key`). Prefer clearer names (`sort_order`, `access_key`).

---

## Primary Keys

| Rule | Convention |
|------|------------|
| Default PK | `id` — unsigned big integer, auto-increment |
| Foreign keys | `{table_singular}_id` | `employee_id`, `company_id` |
| Polymorphic | `subject_type` + `subject_id` (or `owner_type` / `owner_id`) |

Do not use natural keys (email, employee code) as primary keys. Put uniqueness on them separately.

---

## Tables by Domain (logical names)

Names below are the **target vocabulary**. Phased delivery may create them over time.

### Auth / Authorization / System

| Table | Purpose |
|-------|---------|
| `users` | Login identity (`locale` nullable preference: `vi` \| `en`) |
| `roles` | Role definitions |
| `permissions` | Permission definitions |
| `permission_role` | Role ↔ permission |
| `role_user` | User ↔ role |
| `audit_logs` | Append-only audit trail |
| `settings` | System/company settings (as modeled) |

### Organization

| Table | Purpose |
|-------|---------|
| `companies` | Top scope |
| `branches` | Branch under company |
| `departments` | Department |
| `teams` | Team |
| `positions` | Position / job title definition |

### Employee

| Table | Purpose |
|-------|---------|
| `employees` | Master employee record |
| `employee_emergency_contacts` | Emergency contacts |
| `employee_educations` | Education history |
| `employee_work_histories` | Work history |
| `employee_family_members` | Family |
| `employee_contracts` | Contract metadata |
| `employee_bank_accounts` | Bank info |
| `employee_insurances` | Insurance info |
| `employee_tax_profiles` | Tax info |

Prefer `employee_*` prefixes for satellite tables owned by the Employee module.

### Attendance / Leave / Shift

| Table | Purpose |
|-------|---------|
| `attendance_records` | Punches / daily attendance rows |
| `attendance_corrections` | Correction requests |
| `attendance_summaries` | Period summaries (if persisted) |
| `leave_types` | Leave categories |
| `leave_balances` | Balances |
| `leave_requests` | Requests |
| `holidays` | Company holidays |
| `weekend_rules` | Weekend configuration |
| `shifts` | Shift definitions |
| `shift_assignments` | Assignments |
| `overtime_rules` | Overtime schedule rules |

### Payroll

| Table | Purpose |
|-------|---------|
| `salary_structures` / `employee_salaries` | Base salary setup |
| `allowances` / `employee_allowances` | Allowances |
| `bonuses` | Bonuses |
| `deductions` / `employee_deductions` | Deductions |
| `payroll_runs` | A payroll period run |
| `payroll_items` | Per-employee lines in a run |
| `payslips` | Payslip records |

### Recruitment / Onboarding / Performance

| Table | Purpose |
|-------|---------|
| `job_openings` | Recruitment job positions (avoid clashing with org `positions`) |
| `candidates` | Candidates |
| `interviews` | Interviews |
| `candidate_evaluations` | Evaluations |
| `offers` | Offers |
| `onboarding_templates` | Reusable onboarding checklist templates |
| `onboarding_template_items` | Checklist items belonging to a template |
| `onboarding_cases` | Onboarding instances |
| `onboarding_tasks` | Checklist tasks |
| `performance_review_cycles` | Review cycles |
| `performance_goals` | Goals |
| `performance_evaluations` | Evaluations |
| `performance_promotion_suggestions` | Advisory promotion suggestions derived from evaluations |

### Assets / Documents / Notifications

| Table | Purpose |
|-------|---------|
| `assets` | Inventory |
| `asset_assignments` | Custody history |
| `asset_maintenances` | Maintenance |
| `asset_damage_reports` | Damage reports |
| `documents` | File metadata |
| `notifications` | In-app notifications |
| `notification_deliveries` | Channel delivery status (if split) |
| `notification_preferences` | Per-user notification channel/category preferences |
| `report_exports` | Queued report export jobs and their output status |

---

## Indexes & Constraints Naming

| Type | Pattern | Example |
|------|---------|---------|
| Index | `idx_{table}_{columns}` | `idx_employees_company_id_status` |
| Unique | `uq_{table}_{columns}` | `uq_employees_company_id_code` |
| Foreign key | `fk_{table}_{column}` | `fk_employees_company_id` |
| Primary key | `pk_{table}` (optional; MySQL default OK) | `pk_employees` |

Keep names ≤ MySQL identifier limits; abbreviate middle words if required, but keep uniqueness clear.

---

## Enum / Status Columns

- Column name: domain noun or `status` when obvious (`status`, `approval_status`).
- Store as string enums (Laravel) or constrained varchar — prefer readable values: `pending`, `approved`, `rejected`.
- Do not encode status as magic integers without a documented map.

---

## Multi-company Naming

- Company FK is always `company_id` when the row is company-scoped.
- Unique business codes: `uq_{table}_company_id_{code_column}`.

---

## Anti-Patterns

1. Mixing plural/singular randomly (`employee`, `leaves_request`)
2. Hungarian notation (`tbl_employees`, `str_name`)
3. Ambiguous FKs (`user_id` on payroll lines when `employee_id` is meant)
4. Reusing `positions` for both org chart and recruitment openings without disambiguation
5. Abbreviation soup (`emp_att_rec_dt`)

---

## Related Documents

- [DATABASE_CONVENTIONS.md](./DATABASE_CONVENTIONS.md)
- [ERD.md](./ERD.md)
- [TABLE_RELATIONSHIP.md](./TABLE_RELATIONSHIP.md)
