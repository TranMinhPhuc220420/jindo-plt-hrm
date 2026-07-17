# Migration Rules

> Rules for Laravel schema migrations on MySQL.
>
> Conventions: [DATABASE_CONVENTIONS.md](./DATABASE_CONVENTIONS.md) · Naming: [DATABASE_NAMING.md](./DATABASE_NAMING.md)

---

## Principles

1. Migrations are the **only** way to change production schema.
2. Prefer **additive**, expandable changes over destructive ones.
3. Every migration is reviewable, reversible when practical, and tested before production.
4. One clear purpose per migration (or a tightly related set).
5. Schema changes must respect module ownership and dependency rules.

---

## Tooling

| Item | Convention |
|------|------------|
| Framework | Laravel migrations (`database/migrations`) |
| Command (dev) | `php artisan make:migration ...` |
| Apply (dev) | `php artisan migrate` |
| Status | `php artisan migrate:status` |
| Rollback (dev) | `php artisan migrate:rollback` (use carefully) |

Do not hand-edit the database in shared environments and leave schema drift.

---

## Naming Migration Files

Use descriptive snake_case action names:

```
create_employees_table
add_company_id_to_departments_table
add_idx_attendance_records_company_employee_date
create_leave_requests_table
```

Avoid vague names: `update_table`, `fix`, `changes`.

---

## What Belongs in a Migration

Allowed:

- Creating/altering tables and columns
- Indexes and unique constraints
- Foreign keys
- Safe data backfills required to keep the app running after schema change

Not allowed:

- Seeding demo employees/attendance for local DX (use seeders)
- Business logic, jobs, or API calls unrelated to schema readiness
- Dropping audit history to “clean up”

---

## Additive-First Strategy

Prefer this order when evolving schema:

1. Add new nullable column / new table
2. Deploy code that writes both old and new (if transitioning)
3. Backfill data
4. Enforce NOT NULL / constraints
5. Remove old column only when unused and approved

Never drop a column still read by production code.

---

## Destructive Changes

Treat as high risk:

- `dropColumn`, `dropTable`
- Narrowing types
- Changing unique keys that may collide with existing data
- Adding NOT NULL without default/backfill
- Cascading FK deletes on master data

Required for destructive migrations:

1. Explicit PR justification
2. Backup / restore plan
3. Staging rehearsal
4. Rollback plan (or expand/contract with follow-up migration)

---

## Foreign Keys & Indexes

When creating tables:

- Declare FKs intentionally (not always blindly on every reference if load/order requires deferred constraints — document exceptions)
- Add indexes for FKs and common filters in the same migration when possible
- Follow [INDEXING.md](./INDEXING.md) and [DATABASE_NAMING.md](./DATABASE_NAMING.md)

For large existing tables, separate “add index” migrations may be needed for operational safety.

---

## Multi-company & Module Safety

Before adding a table/column, confirm:

- [ ] `company_id` present if company-scoped
- [ ] Owning module is clear ([TABLE_RELATIONSHIP.md](./TABLE_RELATIONSHIP.md))
- [ ] No Attendance↔Payroll ownership inversion
- [ ] Uniques are company-scoped when needed
- [ ] Soft delete / status fields match conventions

---

## Data Migrations

If a migration must transform data:

- Keep it idempotent when re-run is possible
- Batch large updates
- Avoid long locks during peak hours in production
- Prefer Artisan commands for complex backfills invoked deliberately, with a thin migration only when schema requires it

---

## Rollback Rules

- Implement `down()` when safe and meaningful.
- If `down()` would destroy irreversible data, document that the migration is forward-only and why.
- Do not rely on rollback as the primary production recovery strategy — use backup + expand/contract.

---

## Environment Discipline

| Environment | Rule |
|-------------|------|
| Local | Migrate freely; refresh allowed on disposable data |
| CI | Run migrations on fresh DB; tests must pass |
| Staging | Apply the exact migration set intended for production |
| Production | No `migrate:fresh` / `migrate:refresh`; take backup; apply forward migrations |

---

## Review Checklist

- [ ] Descriptive migration name
- [ ] Follows naming & conventions docs
- [ ] FK/index strategy considered
- [ ] No destructive surprise without plan
- [ ] `down()` or explicit forward-only note
- [ ] Tested on empty DB and on DB with representative data when altering
- [ ] Does not break module dependency rules

---

## Anti-Patterns

1. Editing an already-applied migration that others have run (create a new migration instead)
2. One giant “create entire HRM” migration that cannot be reviewed
3. Hardcoding production IDs in migrations
4. Silently changing column meaning without backfill
5. Using raw SQL dumps as a substitute for versioned migrations

---

## Related Documents

- [SEEDING.md](./SEEDING.md)
- [DATABASE_CONVENTIONS.md](./DATABASE_CONVENTIONS.md)
- [INDEXING.md](./INDEXING.md)
- [../01-architecture/DATABASE_ARCHITECTURE.md](../01-architecture/DATABASE_ARCHITECTURE.md)
