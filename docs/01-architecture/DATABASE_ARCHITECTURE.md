# Database Architecture

> High-level data architecture for the HRM platform.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)
>
> Naming, ERD, indexes, migrations: `docs/03-database/`

---

## Purpose

Define how MySQL supports modular domains, multi-company readiness, auditability, and clean repository access — without locking every table definition in this file.

---

## Principles

1. **MySQL is the system of record** for structured HR data.
2. **Company is a top-level scope** on company-owned entities.
3. **Modules own their tables**; other modules access data through services, not ad-hoc joins into foreign internals when avoidable.
4. **Important mutations are auditable** via audit logs (and/or history tables where needed).
5. **Soft boundaries over shared keys** — shared identifiers (employee_id, company_id) are expected; circular ownership is not.
6. **Migrations are additive and reviewable** — follow `docs/03-database/MIGRATION_RULES.md`.

---

## Organizational Hierarchy (data spine)

```
companies
  └── branches
        └── departments
              └── teams
                    └── positions
                          └── employees
```

Employee relationship fields / links may include:

- manager
- direct supervisor
- HR owner
- department head

These relationships support approvals and visibility but must not create circular module dependencies.

---

## Domain Data Groups

| Domain | Typical data concerns |
|--------|------------------------|
| Authentication | Users, credentials, sessions / tokens, password resets, 2FA secrets |
| Authorization | Roles, permissions, role_permission, user_role (or equivalent) |
| Organization | Company, branch, department, team, position |
| Employee | Profile, contacts, education, work history, family, contracts, bank/tax/insurance, status |
| Attendance | Check-in/out, corrections, approvals, daily/period summaries |
| Leave | Types, balances, requests, approvals, holidays, weekend rules |
| Shift | Definitions, assignments, calendars, overtime rules |
| Payroll | Salary structures, allowances, bonuses, deductions, runs, payslips |
| Recruitment | Job positions, candidates, interviews, evaluations, offers |
| Onboarding | Checklists, tasks, probation, completion state |
| Performance | Goals, KPI/OKR, evaluations, review cycles |
| Assets | Inventory, assignments, maintenance, damage reports |
| Documents | File metadata, categories, links to company/employee |
| Notifications | Notification records, delivery status, schedules |
| Reports | Prefer query/read models over duplicated write models |
| Audit Logs | Actor, action, subject, before/after or payload, timestamp |

Exact table names and ERD: `docs/03-database/`.

---

## Multi-company Readiness

Even if v1 activates one company:

- Prefer `company_id` (or equivalent) on company-scoped tables.
- Unique constraints should usually be company-scoped (e.g. employee code unique per company).
- Avoid global uniqueness that blocks SaaS later unless the concept is truly global (e.g. system permission keys).

---

## Cross-Module References

Allowed:

- Foreign keys to stable identity tables (`employees`, `companies`, `users`)
- Read access through owning module services

Discouraged:

- Payroll tables owning attendance rows
- Attendance tables writing payroll results
- Report tables becoming the write path for domain data

Dependency direction: [DEPENDENCY_RULES.md](./DEPENDENCY_RULES.md).

---

## Files vs Database

| Stored in DB | Stored in file storage |
|--------------|------------------------|
| Document metadata, owner, type, permissions | Binary file content |
| Asset metadata and assignment state | Asset photos / attachments if any |
| Employee profile fields | Avatar / scanned contracts / certificates |

See [FILE_STORAGE.md](./FILE_STORAGE.md).

---

## Indexing Guidance (architecture-level)

Index for common access paths:

- company-scoped list filters
- employee_id + date ranges (attendance, leave, payroll)
- status + approval queues
- foreign keys used in joins

Detailed index policy: `docs/03-database/INDEXING.md`.

---

## Auditability

At minimum, audit important actions called out in project logic:

- Employee edited
- Salary changed
- Attendance approved
- Leave rejected
- Asset assigned

Prefer append-only audit records with actor, action, subject type/id, timestamp, and sufficient context to explain the change.

---

## Related Documents

- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
- [BACKEND_ARCHITECTURE.md](./BACKEND_ARCHITECTURE.md)
- `docs/03-database/DATABASE_NAMING.md`
- `docs/03-database/ERD.md`
- `docs/03-database/TABLE_RELATIONSHIP.md`
