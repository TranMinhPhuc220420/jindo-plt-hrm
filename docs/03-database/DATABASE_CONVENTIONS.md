# Database Conventions

> Structural and data conventions for the HRM MySQL schema (Laravel).
>
> Naming details: [DATABASE_NAMING.md](./DATABASE_NAMING.md)
>
> Architecture: [DATABASE_ARCHITECTURE.md](../01-architecture/DATABASE_ARCHITECTURE.md)

---

## Engine & Defaults

| Setting | Convention |
|---------|------------|
| Engine | InnoDB |
| Charset | `utf8mb4` |
| Collation | `utf8mb4_unicode_ci` (or project-standard utf8mb4 collation) |
| Time storage | UTC in database; convert in app/UI |
| Money | `decimal(15,2)` (or documented precision) — never float |
| Percent rates | `decimal` with explicit scale |
| IDs | `BIGINT UNSIGNED` auto-increment unless a documented exception |

---

## Standard Columns

Most business tables include:

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `company_id` | FK nullable only if truly global | Prefer present on company-scoped data |
| `created_at` | TIMESTAMP/DATETIME | |
| `updated_at` | TIMESTAMP/DATETIME | |
| `deleted_at` | TIMESTAMP NULL | Soft deletes when history matters |

Add `created_by` / `updated_by` when actor tracking on the row is useful; still write `audit_logs` for important actions.

---

## Multi-company Readiness

1. Company-owned rows carry `company_id`.
2. Uniqueness for business codes is usually `(company_id, code)`.
3. Global tables are rare: e.g. system `permissions` keys may be global.
4. Queries in repositories/services must scope by company once multi-company is active — design schema so that is natural.
5. v1 may seed a single company; do not omit `company_id` “because one company only”.

---

## Referential Integrity

- Use foreign keys for stable identity relationships (`companies`, `users`, `employees`, org tables).
- Prefer `RESTRICT` / `PROTECT` for deletes of master data that still has dependents.
- Use `CASCADE` only for true ownership children (e.g. deleting a draft parent that owns disposable children) — document each cascade.
- Soft-deleted parents should not break historical child reads; application rules decide visibility.

---

## Module Ownership

| Rule | Meaning |
|------|---------|
| One owner module per table | Documented in [TABLE_RELATIONSHIP.md](./TABLE_RELATIONSHIP.md) |
| Cross-module FKs | Allowed to identity tables (`employees`, `companies`, `users`) |
| Cross-module writes | Through services — not by “borrowing” another module’s tables in ad-hoc migrations without ownership review |

Attendance tables must not store payslip results. Payroll tables must not own raw punch rows.

---

## Status & Lifecycle Fields

- Explicit `status` (or domain-specific status) over deleting rows for state changes.
- Employees move to `archived` rather than hard delete.
- Approval flows use clear statuses: `pending`, `approved`, `rejected`, `cancelled` as applicable.
- Finalized payroll runs should be immutable; corrections use new adjustment runs/rows.

---

## Audit Logs

`audit_logs` (or equivalent) is append-only:

| Field (logical) | Purpose |
|-----------------|---------|
| `actor_type` / `actor_id` | Who |
| `action` | What (`employee.updated`, `leave.rejected`, …) |
| `subject_type` / `subject_id` | Target |
| `company_id` | Scope |
| `payload` / before-after JSON | Context |
| `created_at` | When |

Do not update or delete audit rows in normal application flows.

Minimum audited actions (from project principles):

- Employee edited
- Salary changed
- Attendance approved
- Leave rejected
- Asset assigned

---

## Files Metadata

Documents store metadata only:

- `disk`, `path` / `object_key`, `original_name`, `mime_type`, `size`, `checksum`
- Owner: polymorphic or explicit FKs (`employee_id`, `company_id`, …)
- Never store file bytes in BLOB columns for normal documents

See [FILE_STORAGE.md](../01-architecture/FILE_STORAGE.md).

---

## Nullability

- Prefer NOT NULL for required business fields.
- Use NULL for genuinely optional data.
- Avoid sentinel values (`''`, `0`, `1970-01-01`) meaning “missing”.

---

## Soft Deletes

Use soft deletes when:

- The row is master data or historical evidence
- Reports/audit may need to reference it

Avoid soft deletes for high-churn disposable rows unless there is a retention reason. Define cleanup jobs when needed.

---

## JSON Columns

Allowed for flexible payloads (audit context, notification data, provider metadata) when:

- Fields are not queried as primary filters, or
- MySQL JSON functions/indexes are intentionally planned

Do not hide core relational facts (employee status, leave dates, salary amounts) only inside JSON.

---

## Seed vs Production Data

- Permissions, leave type templates, and demo companies may be seeded.
- Real employees, attendance, and payroll production data are never fabricated by production seeders.

See [SEEDING.md](./SEEDING.md).

---

## Related Documents

- [DATABASE_NAMING.md](./DATABASE_NAMING.md)
- [MIGRATION_RULES.md](./MIGRATION_RULES.md)
- [INDEXING.md](./INDEXING.md)
- [../01-architecture/DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md)
