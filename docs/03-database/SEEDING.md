# Seeding

> Rules for database seeders (Laravel) across local, staging, and production-like environments.
>
> Schema changes belong in [MIGRATION_RULES.md](./MIGRATION_RULES.md), not seeders.

---

## Purpose

Seeders populate **reference data** and **safe demo data** so developers and QA can exercise the HRM flows. They must never become a hidden schema migration tool or corrupt production business data.

---

## Seeder Categories

| Category | Examples | Where allowed |
|----------|----------|---------------|
| System reference | Permissions, base roles, leave type templates, default overtime rule templates | Local, staging, controlled prod bootstrap |
| Organization bootstrap | One demo company, branches/departments skeleton | Local, staging |
| Demo transactional | Fake employees, attendance, leave requests, payroll drafts | Local / staging only |
| Production secrets | Real SMTP keys, real employee PII dumps | Never in seeders |

---

## Laravel Layout (logical)

```
database/seeders/
  DatabaseSeeder.php          # orchestrates
  PermissionSeeder.php
  RoleSeeder.php
  CompanySeeder.php
  DemoEmployeeSeeder.php      # non-production
  ...
```

`DatabaseSeeder` should call seeders in dependency order:

```
Permissions → Roles → Company/Org → Users/Employees → Domain demo data
```

---

## Idempotency

Seeders that may run more than once must be idempotent:

- Prefer `updateOrCreate` / firstOrCreate on natural keys
- Permission keys are stable (`can_approve_leave`, …)
- Do not insert duplicate companies/employees on each `db:seed`

---

## What to Seed by Phase

Align with roadmap phases; do not require full HRM demo data before foundation exists.

| Phase | Seed focus |
|-------|------------|
| Foundation | Permissions, roles, company, org units, admin user, sample employees |
| Time | Leave types, holidays, shifts, sample attendance/leave |
| Payroll | Salary components, draft payroll sample (non-prod) |
| Hire & ops | Job opening, candidate, onboarding checklist templates, assets, documents samples |
| Insight | Notification templates, sample report fixtures if needed |

---

## Production Rules

1. **No `migrate:fresh --seed` on production.**
2. Production bootstrap, if any, is an explicit curated seeder (permissions/roles/settings only).
3. Never seed fake attendance/payroll into production.
4. Never commit real employee PII into seed files.
5. Environment guards:

```php
// conceptual — demo seeders must refuse production
if (app()->environment('production')) {
    return;
}
```

---

## Permissions & Roles

**Source of truth:** [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md).

Rules:

1. `PermissionSeeder` must insert **every** `can_*` key listed in the catalog for modules that have shipped (or are about to ship in the same release).
2. Do not invent keys in seeders that are missing from the catalog — update the catalog first.
3. Roles (Admin, HR, Manager, Employee) receive **bundles** of catalog keys. Business code still checks permissions, not role names.
4. Phase 01 foundation seed must include at least:

| Area | Keys (minimum) |
|------|----------------|
| Organization | `can_view_organization`, `can_manage_organization`, `can_manage_company` |
| Roles | `can_view_roles`, `can_manage_roles`, `can_assign_roles` |
| Settings | `can_view_settings`, `can_manage_settings` |
| Audit | `can_view_audit_logs` |

When Phase 02+ lands, seed the matching catalog sections (Employee, Attendance, …) in the same release — never leave API routes without seeded keys.

See also [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md) and [ROLES_API.md](../06-api/ROLES_API.md).

---

## Multi-company Readiness

- Seed at least one `companies` row in demo environments.
- All demo org/employee rows should set `company_id`.
- Optional second company only for explicit multi-company testing — keep default DX simple.

---

## Demo Data Quality

Good demo data:

- Covers happy path + one approval rejection path
- Uses obvious fake names/emails (`@example.test`)
- Has deterministic codes for assertions in tests when needed

Bad demo data:

- Random huge volumes by default (optional stress seeder separately)
- Obscure dependencies that fail if run partially
- Hardcoded IDs that break when run order changes

---

## Relationship to Factories & Tests

| Tool | Use |
|------|-----|
| Seeders | Human-usable baseline datasets |
| Factories | Tests and on-demand fake models |
| Migrations | Schema only |

Feature tests should not depend on full demo seeders unless intentionally written as such; prefer factories for isolation.

---

## Checklist

Before adding a seeder:

- [ ] Category clear (reference vs demo)
- [ ] Idempotent
- [ ] Guarded from production if demo/PII-like
- [ ] Respects FK/dependency order
- [ ] Permission keys match [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md) / company scope
- [ ] No schema DDL inside seeder

---

## Related Documents

- [MIGRATION_RULES.md](./MIGRATION_RULES.md)
- [DATABASE_CONVENTIONS.md](./DATABASE_CONVENTIONS.md)
- [../01-architecture/PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md)
- [../01-architecture/AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md)
- [../00-overview/BUSINESS_SCOPE.md](../00-overview/BUSINESS_SCOPE.md)
- [../09-roadmap/](../09-roadmap/)
