# ERD

> Logical entity-relationship overview for the HRM platform.
>
> This is a **logical ERD** for documentation and design alignment — not a generated dump of every column.
>
> Naming: [DATABASE_NAMING.md](./DATABASE_NAMING.md) · Relationships: [TABLE_RELATIONSHIP.md](./TABLE_RELATIONSHIP.md)

---

## How to Read

- Boxes are entities/tables (logical).
- Arrows show FK direction: `child → parent` means child holds the FK.
- Phased delivery creates subsets of this model over time.
- Satellite employee tables are summarized where helpful.

---

## 1. Organization Spine

```mermaid
erDiagram
  companies ||--o{ branches : has
  companies ||--o{ departments : has
  companies ||--o{ teams : has
  companies ||--o{ positions : has
  companies ||--o{ employees : has
  branches ||--o{ departments : contains
  departments ||--o{ teams : contains
  departments ||--o{ employees : places
  teams ||--o{ employees : places
  positions ||--o{ employees : titles
  employees ||--o| employees : manager_of
```

Notes:

- Exact parentage (branch vs department required) is a product rule; schema should allow company-scoped hierarchy.
- Manager / supervisor / HR owner may be self-FKs or separate relation fields on `employees`.

---

## 2. Auth & Authorization

```mermaid
erDiagram
  users ||--o| employees : linked_to
  users ||--o{ role_user : has
  roles ||--o{ role_user : has
  roles ||--o{ permission_role : has
  permissions ||--o{ permission_role : has
  users ||--o{ audit_logs : acts
```

---

## 2b. Settings (company-scoped)

```mermaid
erDiagram
  companies ||--o{ settings : configures
```

Logical model (implementation may use one row-per-key or JSON groups):

| Field (logical) | Meaning |
|-----------------|---------|
| `company_id` | Tenant scope (required for business settings) |
| `group` | Namespace: `company`, `auth`, `attendance`, … |
| `key` | Stable setting key within group |
| `value` | Typed JSON / string value |
| timestamps | `created_at` / `updated_at` |

Rules:

- Unique on `(company_id, group, key)` (or equivalent).
- Secrets (SMTP passwords, API keys) stay in env/secret manager — not in `settings`.
- API surface: [SETTINGS_API.md](../06-api/SETTINGS_API.md).

Global/system-only keys (rare) may use `company_id` null only if explicitly documented; prefer company-scoped defaults for SaaS readiness.

---

## 3. Employee Cluster

```mermaid
erDiagram
  companies ||--o{ employees : owns
  employees ||--o{ employee_emergency_contacts : has
  employees ||--o{ employee_educations : has
  employees ||--o{ employee_work_histories : has
  employees ||--o{ employee_family_members : has
  employees ||--o{ employee_contracts : has
  employees ||--o{ employee_bank_accounts : has
  employees ||--o{ employee_insurances : has
  employees ||--o| employee_tax_profiles : has
  employees ||--o{ documents : files
```

---

## 4. Time Domain (Shift · Attendance · Leave)

```mermaid
erDiagram
  companies ||--o{ shifts : defines
  companies ||--o{ overtime_rules : defines
  employees ||--o{ shift_assignments : assigned
  shifts ||--o{ shift_assignments : used_by
  employees ||--o{ attendance_records : punches
  employees ||--o{ attendance_corrections : requests
  attendance_records ||--o{ attendance_corrections : corrects
  companies ||--o{ leave_types : defines
  employees ||--o{ leave_balances : holds
  leave_types ||--o{ leave_balances : for
  employees ||--o{ leave_requests : requests
  leave_types ||--o{ leave_requests : of_type
  companies ||--o{ holidays : defines
  companies ||--o{ weekend_rules : defines
```

Payroll reads attendance summaries via services; it does not own punch tables.

---

## 5. Payroll

```mermaid
erDiagram
  companies ||--o{ payroll_runs : runs
  employees ||--o{ employee_salaries : compensated
  employees ||--o{ payroll_items : appears_in
  payroll_runs ||--o{ payroll_items : contains
  payroll_items ||--o| payslips : generates
  employees ||--o{ payslips : receives
```

Allowances, bonuses, deductions attach to employees and/or payroll items as implemented — keep ownership inside Payroll.

---

## 6. Hire Path (Recruitment → Onboarding → Employee)

```mermaid
erDiagram
  companies ||--o{ job_openings : opens
  job_openings ||--o{ candidates : attracts
  candidates ||--o{ interviews : has
  candidates ||--o{ candidate_evaluations : has
  candidates ||--o{ offers : receives
  offers ||--o| onboarding_cases : starts
  onboarding_cases ||--o{ onboarding_tasks : includes
  onboarding_cases ||--o| employees : activates
  candidates ||--o| employees : becomes
```

Candidates are not employees until hire/onboarding activation policy says so.

---

## 7. Performance

```mermaid
erDiagram
  companies ||--o{ performance_review_cycles : runs
  performance_review_cycles ||--o{ performance_evaluations : includes
  employees ||--o{ performance_evaluations : evaluated
  employees ||--o{ performance_goals : sets
```

---

## 8. Assets · Documents · Notifications · Audit · Settings

```mermaid
erDiagram
  companies ||--o{ assets : owns
  assets ||--o{ asset_assignments : history
  employees ||--o{ asset_assignments : custodian
  assets ||--o{ asset_maintenances : maintained
  assets ||--o{ asset_damage_reports : damaged
  companies ||--o{ documents : files
  employees ||--o{ documents : files
  users ||--o{ notifications : receives
  companies ||--o{ audit_logs : scoped
  companies ||--o{ settings : configures
```

Settings detail: §2b above. Audit writes are append-only from domain services/listeners ([AUDIT_API.md](../06-api/AUDIT_API.md) is read-only).

---

## Cross-Cutting Identity

Stable identity hubs used across modules:

| Entity | Referenced by |
|--------|----------------|
| `companies` | Almost all business tables, including `settings` |
| `users` | Auth, notifications, audit actors, role assignments |
| `employees` | Attendance, leave, shift, payroll, assets, performance, documents |
| `settings` | Modules read via Settings service (not ad-hoc env for business rules) |

---

## Explicit Non-Edges (dependency safety)

Do **not** model these ownership edges:

| Forbidden ownership | Why |
|---------------------|-----|
| `attendance_records` → `payslips` as child ownership | Payroll consumes attendance; does not invert ownership |
| `reports_*` as parent of domain writes | Reports are read-oriented |
| `notifications` owning leave/payroll rows | Notifications carry references only |

---

## Related Documents

- [TABLE_RELATIONSHIP.md](./TABLE_RELATIONSHIP.md)
- [DATABASE_NAMING.md](./DATABASE_NAMING.md)
- [../01-architecture/DATABASE_ARCHITECTURE.md](../01-architecture/DATABASE_ARCHITECTURE.md)
- [../02-business/README.md](../02-business/README.md)
