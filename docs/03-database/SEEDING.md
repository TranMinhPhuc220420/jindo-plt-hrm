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
| Foundation | Permissions, roles, company, org units, admin user |
| Employee (Phase 02) | Sample employees (non-prod) |
| Shift (Phase 05) | Shift definitions, sample assignments, default OT rule (non-prod) |
| Attendance (Phase 03) | Sample attendance records (non-prod) |
| Leave (Phase 04) | Leave types, holidays, weekend rules, sample balances/requests (non-prod) |
| Payroll (Phase 06) | Salary + allowance/deduction for E-0001; sample finalized run + payslip (non-prod) |
| Hire & ops (Phase 07) | Company policy document, one available asset, one open job + screening candidate, default onboarding template with checklist items (non-prod) |
| Insight (Phase 08) | Sample notification inbox for the admin user; one active performance review cycle with goals for E-0001 (non-prod) |

---

## Commands by environment

| Environment | Command |
|-------------|---------|
| Local / staging demo | `php artisan db:seed` or `make seed` / `make seed-local` → [`DatabaseSeeder`](../../database/seeders/DatabaseSeeder.php) (full demo) |
| Production bootstrap | `php artisan db:seed --class=ProductionBootstrapSeeder --force` or `make seed-admin` |
| Production via `db:seed` | When `APP_ENV=production`, `DatabaseSeeder` delegates to `ProductionBootstrapSeeder` only |

### Production bootstrap (`ProductionBootstrapSeeder`)

Curated, idempotent reference data — **not** a data wipe:

| Seeds | Notes |
|-------|--------|
| Permissions + system roles | Same `PermissionSeeder` / `RoleSeeder` as local |
| Company | `SEED_COMPANY_CODE` / `SEED_COMPANY_NAME` (defaults `JINDO` / `Jindo`) |
| Settings defaults | Via `SettingsService::seedDefaultsForCompany` |
| Shift definitions + `STANDARD` OT | MORNING / NIGHT — **no** assignments or employees |
| Admin user | Requires `SEED_ADMIN_EMAIL` + `SEED_ADMIN_PASSWORD`; assigns Admin role |

Does **not** seed demo employees, org tree, attendance, leave, payroll, hire/ops, or insight samples.

Env (see `.env.example` / `config/hrm.php`):

- `SEED_COMPANY_CODE`, `SEED_COMPANY_NAME` — optional (defaults above)
- `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD` — **required** or the seeder throws

Safe to run on staging with the same class for smoke tests.

---

## Production Rules

1. **No `migrate:fresh --seed` on production.**
2. Production bootstrap is `ProductionBootstrapSeeder` (permissions, roles, company, settings, shifts, admin) — never demo transactional data.
3. Never seed fake attendance/payroll into production.
4. Never commit real employee PII into seed files.
5. Environment guards:

```php
// conceptual — demo seeders must refuse production
if (app()->environment('production')) {
    return;
}
```

6. If production already contains local demo junk, clean it operationally — the bootstrap seeder does not delete rows.

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
