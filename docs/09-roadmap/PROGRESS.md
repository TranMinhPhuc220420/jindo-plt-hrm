# Project Progress Tracker

> **Living checklist** — update after every agent / developer step completion.
>
> Phase scope: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md) · [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md)
>
> Binding stack: [STACK_DECISION.md](../01-architecture/STACK_DECISION.md)
>
> **Language:** All content in this file must be written in **English**.

---

## How to update (agents & humans)

1. Read this file **before** starting a new task — work only on the current phase’s **`In progress` step** or the next **`Todo`** step.
2. When starting a step: set status → `In progress`, fill `Started`.
3. When finishing a step: set status → `Done`, tick checkboxes, fill `Completed`, add a short Notes entry (key files / tests / risks).
4. **Do not skip ahead** to a later phase until the current phase Exit Criteria are met.
5. When a whole phase is Done: update the Phase Overview table and the status in [MASTER_ROADMAP.md](./MASTER_ROADMAP.md).

### Status values

| Status | Meaning |
|--------|---------|
| `Todo` | Not started |
| `In progress` | Actively being worked on (only **one** step should be In progress) |
| `Done` | Finished with minimum verification |
| `Blocked` | Cannot continue — record the reason in Notes |
| `Skipped` | Intentionally skipped (rare) — must include a reason |

### Legend (checkboxes)

- `[ ]` not done · `[x]` done

---

## Snapshot

| Field | Value |
|-------|--------|
| **Current phase** | ∞ Future |
| **Current step** | `∞.test-push-button` |
| **Overall status** | Done |
| **Last updated** | 2026-09-05 |
| **Last updated by** | agent |
| **Next action** | Future backlog / discuss `v1.0.0` readiness |
| **Blockers** | None |

---

## Phase Overview

| Phase | Name | Status | Milestone |
|-------|------|--------|-----------|
| 01 | Foundation | Done | `v0.1.0` |
| 02 | Employee | Done | `v0.2.0` |
| 03 | Attendance | Done | `v0.3.0` (time domain) |
| 04 | Leave | Done | `v0.3.0` |
| 05 | Shift | Done | `v0.3.0` — **before / alongside full Attendance rules** |
| 06 | Payroll | Done | `v0.4.0` |
| 07 | Recruitment & Ops | Done | `v0.5.0` |
| 08 | Insight | Done | `v0.6.0` |
| ∞ | Future | Backlog | — |

Recommended time-domain order: **05 → 03 → 04** (dependencies matter more than file numbers).

---

## Phase 01 — Foundation (step-by-step)

> Goal: Auth, authz, org, settings, audit writer, app shell.  
> Spec: [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md)

**Phase status:** `Done` (hardening `01.11` complete)  
**Do not start Phase 02 until Exit Criteria below are all `[x]`.**

### 01.0 — Environment setup

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | `.env.example`, [DEPLOYMENT.md](../08-development/DEPLOYMENT.md) |

- [x] MySQL database `jindo_plt_hrm` created and reachable
- [x] `.env` matches local setup (DB, `APP_URL`, session)
- [x] `composer install` / `npm install` OK
- [x] `php artisan migrate` succeeds (existing migrations)
- [x] `composer run dev` / app boot (Laravel + Vite) OK
- [x] Working branch for Phase 01 (e.g. `feature/phase-01-foundation`)

**Notes:**

```
- Branch: feature/phase-01-foundation
- MySQL via XAMPP (/opt/lampp); DB jindo_plt_hrm already existed; tables: users, sessions, jobs, passkeys, etc.
- migrate: Nothing to migrate
- HTTP smoke: php artisan serve → GET / returned 200
- Stack: Laravel 13.20 / PHP 8.5 / starter kit (Inertia + Fortify); Sanctum not installed yet (01.1)
```

---

### 01.1 — Sanctum + API platform spine

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [STACK_DECISION.md](../01-architecture/STACK_DECISION.md), [API_RESPONSE.md](../04-backend/API_RESPONSE.md), [ERROR_HANDLING.md](../04-backend/ERROR_HANDLING.md), [REST_STANDARD.md](../06-api/REST_STANDARD.md) |

- [x] Install and configure Laravel Sanctum (SPA cookie)
- [x] Register `routes/api.php` in bootstrap
- [x] CORS / `SANCTUM_STATEFUL_DOMAINS` / session cookie for first-party SPA
- [x] Shared API success/error envelope helpers
- [x] Exception → JSON error mapping (422/401/403/404/…)
- [x] Smoke: `/api/health` (or equivalent) returns the envelope

**Notes:**

```
- laravel/sanctum ^4.3; personal_access_tokens migrated; User uses HasApiTokens
- bootstrap: api routes + statefulApi(); ApiResponse + DomainException + exception envelope
- /api/health OK; CORS credentials + SANCTUM_STATEFUL_DOMAINS in .env/.env.example
- Tests: Feature/Api/HealthTest, Unit/Support/ApiResponseTest (4 passed)
```

---

### 01.2 — Auth API + `/api/me`

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [AUTHENTICATION.md](../01-architecture/AUTHENTICATION.md), [AUTH_API.md](../06-api/AUTH_API.md) |

- [x] Login (Sanctum SPA / Fortify behind AUTH_API)
- [x] Logout
- [x] CSRF / cookie flow documented in code comments or README if non-obvious
- [x] `GET /api/me` returns user identity
- [x] Forgot / reset password (per AUTH_API — happy path minimum)
- [x] Feature tests: login, logout, unauthenticated 401

**Notes:**

```
- Routes: POST /api/auth/login|logout|forgot-password|reset-password, GET /api/me
- AuthService + Form Requests; 2FA challenge returns two_factor_required (full 2FA API later)
- permissions[] empty until 01.3; CSRF note in routes/api.php
- Tests: Feature/Api/AuthApiTest (6) — all green with Health + ApiResponse
```

---

### 01.3 — Permissions, roles, policies pattern

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md), [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md), [ROLES_API.md](../06-api/ROLES_API.md), [SEEDING.md](../03-database/SEEDING.md) |

- [x] Migrations: permissions, roles, pivots (users↔roles, roles↔permissions)
- [x] `PermissionSeeder` — Foundation keys from the catalog
- [x] `RoleSeeder` — Admin / HR / Manager / Employee bundles
- [x] `/api/me` includes permission list
- [x] Roles CRUD API (per ROLES_API)
- [x] Sample policy / gate pattern (reuse for later modules)
- [x] Tests: 403 when permission missing; never authorize by role name

**Minimum Foundation keys:**

- `can_view_organization`, `can_manage_organization`, `can_manage_company`
- `can_view_roles`, `can_manage_roles`, `can_assign_roles`
- `can_view_settings`, `can_manage_settings`
- `can_view_audit_logs`

**Notes:**

```
- Tables: permissions, roles, permission_role, role_user
- Gate::before maps can_* → User::hasPermission; RolePolicy + UserPolicy
- APIs: /api/permissions, /api/roles CRUD + permissions sync, /api/users/{id}/roles
- Seed: admin@example.test / password with Admin role
- Tests: Feature/Api/RolesApiTest (6) — 14 API tests total green
```

---

### 01.4 — Organization hierarchy

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [02-business/organization](../02-business/organization/README.md), [ORGANIZATION_API.md](../06-api/ORGANIZATION_API.md), [ERD.md](../03-database/ERD.md) |

- [x] Migrations: company, branch, department, team, position (+ indexes)
- [x] Models + services (thin controllers)
- [x] CRUD APIs under company scope
- [x] Policies using Foundation permissions
- [x] Seed: one demo company + org skeleton (non-prod)
- [x] Tests: CRUD happy path + 403 + validation 422

**Notes:**

```
- CompanyContext for v1 current company; soft deletes on org nodes
- APIs: /api/companies/current, branches, departments, teams, positions, /api/organization/tree
- CompanySeeder: JINDO + HQ + ENG/HR + team + positions
- Tests: Feature/Api/OrganizationApiTest (4) — 18 API tests total green
```

---

### 01.5 — Settings

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [02-business/settings](../02-business/settings/README.md), [SETTINGS_API.md](../06-api/SETTINGS_API.md) |

- [x] Settings storage (table / key-value per docs)
- [x] Read/update APIs + permissions
- [x] Seed defaults required by the app
- [x] Tests: view/manage permission gates

**Notes:**

```
- settings table (company_id, group, key, json value); SettingsDefaults company/auth
- GET/PUT /api/settings, GET /api/settings/{group}; seeded with CompanySeeder
- Tests: Feature/Api/SettingsApiTest (3) green
```

---

### 01.6 — Audit log writer

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [02-business/audit](../02-business/audit/README.md), [AUDIT_API.md](../06-api/AUDIT_API.md) |

- [x] `audit_logs` (or name per database naming docs) migration
- [x] Audit writer service reusable by other modules
- [x] Write audit for at least one sample mutation (e.g. org update / role assign)
- [x] Minimal list API + `can_view_audit_logs` (full UI may be thin)
- [x] Tests: writer creates a record; unauthorized list = 403

**Notes:**

```
- AuditLogger::write(); wired into settings.updated + company.updated
- GET /api/audit-logs (+ show); append-only table with morph actor/subject
- Tests: Feature/Api/AuditApiTest (2) — 23 API tests total green
```

---

### 01.7 — Frontend API client + auth flow

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [API_CLIENT.md](../05-frontend/API_CLIENT.md), [AUTH_API.md](../06-api/AUTH_API.md), [STACK_DECISION.md](../01-architecture/STACK_DECISION.md) |

- [x] Shared API client (CSRF, credentials, envelope parse, 401/422 handling)
- [x] Login / logout UI calls REST (do not load domain data via Inertia props)
- [x] Load `/api/me` into auth state
- [x] Permission helpers / `PermissionGate` stub
- [x] Do not expand Inertia demo pages for new HRM domains

**Notes:**

```
- lib/api/{client,errors,types} + modules/auth; AuthProvider + PermissionGate
- Login page POST /api/auth/login; logout via /api/auth/logout
- tsc --noEmit clean
```

---

### 01.8 — App shell + design tokens

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [LAYOUT.md](../05-frontend/LAYOUT.md), [ROUTING.md](../05-frontend/ROUTING.md), [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md), [07-uiux/stitch](../07-uiux/stitch/) |

- [x] Efficient Growth / Stitch design tokens in Tailwind
- [x] Auth layout + app sidebar shell (desktop)
- [x] Gated route stubs: `/organization`, `/roles`, `/settings`, `/audit-logs`
- [x] Nav show/hide by permissions
- [x] Minimum responsive behavior (desktop + mobile web)

**Notes:**

```
- Tokens: primary-brand #059669, primary-deep #006948, chrome #f2f2f7
- Sidebar gates: organization/roles/settings/audit; HRM settings at /settings/company
- Stub Inertia pages + AdminPageShell; CRUD UI deferred to 01.9
```

---

### 01.9 — Foundation UI pages (thin CRUD)

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | Org / Roles / Settings / Audit API + UI guidelines |

- [x] Organization management UI (usable CRUD)
- [x] Roles & permissions UI
- [x] Settings UI
- [x] Audit logs list UI (may be minimal)
- [x] Empty / loading / error states per FE guidelines

**Notes:**

```
- API modules under lib/api/modules/{organization,roles,settings,audit}
- Pages: org tree + create branch/dept/position; roles permission editor; settings form; audit table
- Loading/Empty/Error shared components; tsc clean
```

---

### 01.10 — Phase 01 exit: tests, seed, smoke, tag

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md) Exit Criteria · [SEEDING.md](../03-database/SEEDING.md) |

- [x] `PermissionSeeder` + `RoleSeeder` + org + admin user idempotent
- [x] Feature tests cover authz deny paths
- [x] Manual smoke: login → me → org CRUD → roles → settings → audit
- [x] CI baseline (if present) green with Phase 01 tests
- [ ] Tag / note milestone `v0.1.0` (when the user requests a release)
- [x] Update Phase Overview above → `Done`
- [x] Update Phase 01 status in [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)

**Notes:**

```
- 23 API feature tests green; frontend types:check green
- Seed: admin@example.test / password + JINDO company skeleton
- Git tag v0.1.0 deferred until user asks
- Browser smoke of full UI left as optional local verification
```

---

### 01.11 — Phase 01 hardening (pre–Phase 02)

> Close auth/UI/audit gaps found after Foundation Done. Do not start Phase 02 until this section is Done.

#### 01.11a — Auth: 2FA challenge + password REST UI

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [AUTH_API.md](../06-api/AUTH_API.md) |

- [x] `POST /api/auth/two-factor/challenge` + AuthService
- [x] Login UI OTP/recovery after `two_factor_required`
- [x] Forgot / reset password pages use REST
- [x] Feature tests: challenge success/fail / no pending

**Notes:**

```
- AuthService::challengeTwoFactor; enrollment remains Fortify /settings/security
- Login/forgot/reset use REST; AuthApiTest 8 green
```

#### 01.11b — Settings routing + shell UX

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [ROUTING.md](../05-frontend/ROUTING.md) |

- [x] `settings/company` uses AppLayout only (not starter SettingsLayout)
- [x] `/settings` redirects to `/settings/company`
- [x] Remove AdminPageShell stub copy

#### 01.11c — Organization UI: usable CRUD

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [ORGANIZATION_API.md](../06-api/ORGANIZATION_API.md) |

- [x] Branch/dept/team/position update + delete in UI
- [x] Team create form
- [x] Company inline edit (`can_manage_company`)
- [x] API tests: BRANCH_HAS_DEPARTMENTS + update happy path

#### 01.11d — Roles assign UI + audit wiring

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [ROLES_API.md](../06-api/ROLES_API.md), [AUDIT_API.md](../06-api/AUDIT_API.md) |

- [x] Assign roles to user by id (`can_assign_roles`)
- [x] Audit on role + org mutations
- [x] Tests: audit row after sync / branch create

#### 01.11e — Docs polish

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | PHASE_01, SEEDING, ROUTING |

- [x] Tick PHASE_01 Exit Criteria
- [x] SEEDING: no sample employees in Foundation
- [x] ROUTING notes for `/settings/company` vs profile

#### 01.11f — Verification exit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | PROGRESS Snapshot |

- [x] API suite + types:check green
- [x] Manual smoke notes recorded
- [ ] Tag `v0.1.0` still deferred until user asks
- [x] Snapshot → Next action Phase 02

**Notes:**

```
- Feature/Api: 28 tests passed; npm run types:check clean
- Smoke checklist (API-verified): login/me, 2FA challenge recovery path, org CRUD constraints, role assign audit, settings audit
- Browser UI smoke left to local verification with admin@example.test / password
- Git tag v0.1.0 still deferred
```

---

### 01.12 — i18n foundation (vi / en)

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [I18N.md](../05-frontend/I18N.md), [AUTH_API.md](../06-api/AUTH_API.md), [SETTINGS_API.md](../06-api/SETTINGS_API.md) |

- [x] Docs: I18N.md + settings/auth API + AI agent rules
- [x] Backend: `users.locale`, LocaleResolver, SetLocale, `PUT /api/me/locale`, default `company.locale` = `vi`
- [x] Frontend: react-i18next + LanguageSwitcher + catalogs
- [x] Translate existing Foundation/Employee UI (vi + en)
- [x] Tests + types:check green

**Notes:**

```
- Effective locale: user.locale ?? company.locale ?? APP_LOCALE (vi)
- PUT /api/me/locale + company.locale select; AuthPayload includes locale fields
- Catalogs: resources/js/locales/{vi,en}/*.json; shell + auth + admin modules translated
- Feature/Api: 37 tests passed; npm run types:check clean
```

---

### 01.13 — i18n hardening (missing / incorrect messages)

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |
| Docs | [I18N.md](../05-frontend/I18N.md) |

- [x] Domain status + weekday labels (leave / attendance / payroll)
- [x] Starter-kit security / passkeys / 2FA / appearance / delete-user
- [x] Copy fixes (employee name order, roles assign, common true/false)
- [x] Backend `lang/{vi,en}` + DomainException `__()`
- [x] Docs sync + tests / types:check

**Notes:**

```
- Status maps: leave/attendance/payroll status.*; weekdays in common; async-state uses t('loading')
- Starter-kit: delete-user, passkeys, 2FA manage/modal/recovery, appearance tabs, alert-error
- Copy: employee create Họ→Tên order; roles assign help; common true/false Có/Không; attendance Vào ca/Ra ca
- DomainException::__() + lang/vi.json (75 keys) + lang/{vi,en}/domain.php interpolations
- Unit DomainExceptionLocaleTest + Feature/Api green; types:check clean
```

---

### Phase 01 — Exit Criteria (rollup)

- [x] User login/logout; `/api/me` returns permissions
- [x] Permissions DB-driven; Foundation catalog keys seeded
- [x] Org hierarchy CRUD under company scope
- [x] Settings + roles APIs usable; SPA routes gated
- [x] App shell matches Stitch sidebar; Admin nav permission-aware
- [x] Audit writer usable by later modules
- [x] Tests cover authz deny paths

---

## Later phases (light checklist — expand when the phase starts)

> Mark Done only when that phase doc’s Exit Criteria are met. Add detailed steps to this file at phase kickoff (same granularity as Phase 01).

### Phase 02 — Employee (step-by-step)

> Goal: Employee master + satellites, sensitive fields, status lifecycle, UI.  
> Spec: [PHASE_02_EMPLOYEE.md](./PHASE_02_EMPLOYEE.md)

**Phase status:** `Done`

#### 02.0 — Kickoff

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [PHASE_02_EMPLOYEE.md](./PHASE_02_EMPLOYEE.md) |

- [x] Branch `feature/phase-02-employee`
- [x] PROGRESS expanded with steps 02.0–02.9

#### 02.1 — Permissions + role bundles

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] Employee keys in PermissionCatalog + seeder
- [x] Admin/HR/Manager/Employee role bundles updated

#### 02.2 — Schema + models

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] `employees` + satellite migrations
- [x] Models + factories

#### 02.3 — Core Employee API

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] CRUD + list filters + status + DELETE archive
- [x] EmployeePolicy + audit on mutations

#### 02.4 — Satellite APIs

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] Emergency contacts, educations, work histories, family, contracts

#### 02.5 — Sensitive APIs

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] Bank / tax / insurance gated by `can_manage_employee_sensitive`

#### 02.6 — Feature tests

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] EmployeeApiTest: CRUD, filters, 403 sensitive, status 409, audit

#### 02.7 — Frontend

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] API module + list/create/detail pages + nav
- [x] `/api/me` returns `employee_id` when linked

#### 02.8 — Seed demo employees

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] EmployeeSeeder (non-prod) + DatabaseSeeder order

#### 02.9 — Phase exit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] Exit criteria + suite green
- [ ] Tag `v0.2.0` deferred until user asks
- [x] Snapshot → recommend Phase 05 next

**Notes:**

```
- Feature/Api: 34 tests passed; types:check clean
- Branch: feature/phase-02-employee
- Seed: E-0001..E-0003 under JINDO; admin linked to E-0001
- Tag v0.2.0 deferred
```

### Phase 05 — Shift _(recommended before full Attendance)_

> Goal: Shift definitions, assignments, working calendar, overtime rules.  
> Spec: [PHASE_05_SHIFT.md](./PHASE_05_SHIFT.md)

**Phase status:** `Done`

#### 05.0 — Kickoff

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |
| Docs | [PHASE_05_SHIFT.md](./PHASE_05_SHIFT.md) |

- [x] Branch `feature/phase-05-shift`
- [x] PROGRESS expanded with steps 05.0–05.9

#### 05.1 — Permissions + role bundles

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] Shift keys in PermissionCatalog + seeder
- [x] Admin/HR/Manager/Employee role bundles updated

#### 05.2 — Schema + models

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] `shifts` + `shift_assignments` + `overtime_rules` migrations
- [x] Models + factories

#### 05.3 — Core Shift definition API

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] CRUD + list filters + soft-delete / SHIFT_IN_USE
- [x] ShiftPolicy + audit on mutations

#### 05.4 — Assignments + WorkingCalendarService

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] Assignment CRUD + overlap detection
- [x] Working calendar resolve API + service smoke contract

#### 05.5 — Overtime rules API

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] GET + PUT replace-set for company OT rules

#### 05.6 — Feature tests

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] ShiftApiTest: CRUD, overlap, calendar, own-schedule, audit

#### 05.7 — Frontend

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] API module + list/create/detail + my-schedule + nav + i18n

#### 05.8 — Seed demo shifts

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] ShiftSeeder (non-prod) + DatabaseSeeder order

#### 05.9 — Phase exit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-17 |
| Completed | 2026-07-17 |

- [x] Exit criteria + suite green
- [ ] Tag `v0.3.0` deferred until user asks
- [x] Snapshot → recommend Phase 03 next

**Notes:**

```
- Feature/Api: 47 tests passed; types:check clean
- Branch: feature/phase-05-shift
- Seed: MORNING/NIGHT + STANDARD OT; assignment for E-0001
- Rotating cycle engine deferred; is_holiday stubbed false
- Tag v0.3.0 deferred
```

### Phase 03 — Attendance

> Goal: Manual check-in/out, corrections, approvals, summaries.  
> Spec: [PHASE_03_ATTENDANCE.md](./PHASE_03_ATTENDANCE.md)

**Phase status:** `Done`

#### 03.0 — Kickoff

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |
| Docs | [PHASE_03_ATTENDANCE.md](./PHASE_03_ATTENDANCE.md) |

- [x] Branch `feature/phase-03-attendance`
- [x] PROGRESS expanded with steps 03.0–03.9

#### 03.1 — Permissions + role bundles

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Attendance keys in PermissionCatalog + seeder
- [x] Admin/HR/Manager/Employee role bundles updated

#### 03.2 — Schema + models

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] `attendance_records` + `attendance_corrections` migrations
- [x] Models + factories

#### 03.3 — Punch + metrics

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Check-in/out + WorkingCalendarService metrics

#### 03.4 — Records + approve + lock

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] List/detail + approve + period lock

#### 03.5 — Corrections + summary

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Corrections approve/reject + compute-only summary

#### 03.6 — Feature tests

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] AttendanceApiTest: double check-in, lock, audit, summary

#### 03.7 — Frontend

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] API module + pages + nav + i18n

#### 03.8 — Seed

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] AttendanceSeeder (non-prod) + DatabaseSeeder order

#### 03.9 — Phase exit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Exit criteria + suite green
- [ ] Tag `v0.3.0` deferred until user asks
- [x] Snapshot → recommend Phase 04 next

**Notes:**

```
- Feature/Api: 55 tests passed; types:check clean
- Branch: feature/phase-03-attendance
- Consumes WorkingCalendarService; no payroll writes
- Tag v0.3.0 deferred
```

### Phase 04 — Leave

> Goal: Leave types, balances, requests, holidays, weekend rules.  
> Spec: [PHASE_04_LEAVE.md](./PHASE_04_LEAVE.md)

**Phase status:** `Done`

#### 04.0 — Kickoff

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |
| Docs | [PHASE_04_LEAVE.md](./PHASE_04_LEAVE.md) |

- [x] Branch `feature/phase-04-leave`
- [x] PROGRESS expanded with steps 04.0–04.9

#### 04.1 — Permissions + role bundles

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Leave keys in PermissionCatalog + seeder
- [x] Admin/HR/Manager/Employee role bundles updated

#### 04.2 — Schema + models

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] `leave_types` + `leave_balances` + `leave_requests` + `holidays` + `weekend_rules` migrations
- [x] Models + factories

#### 04.3 — Calendar config APIs + WorkingCalendar wire

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Leave types / holidays / weekend-rules APIs
- [x] WorkingCalendarService `is_holiday` from holidays + weekend rules

#### 04.4 — Balances + request create/cancel

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Balance list/adjust + duration calculator
- [x] Request create/cancel with overlap and balance reserve

#### 04.5 — Approve/reject + events + audit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Approve/reject with balance finalize
- [x] Audit + domain event notification hooks

#### 04.6 — Feature tests

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] LeaveApiTest: balance, insufficient, holidays, overlap, approve scope, transitions

#### 04.7 — Frontend

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] API module + pages + nav + i18n

#### 04.8 — Seed

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] LeaveSeeder (non-prod) + DatabaseSeeder order

#### 04.9 — Phase exit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Exit criteria + suite green
- [ ] Tag `v0.3.0` deferred until user asks
- [x] Snapshot → recommend Phase 06 next

**Notes:**

```
- Feature/Api: 104 tests passed; types:check clean
- Branch: feature/phase-04-leave
- WorkingCalendarService is_holiday from holidays + weekend_rules
- Notification hooks: LogLeaveNotification (system-first)
- Tag v0.3.0 deferred
```

### Phase 06 — Payroll

> Goal: Monthly salary, components, runs, approval, payslips.  
> Spec: [PHASE_06_PAYROLL.md](./PHASE_06_PAYROLL.md)

**Phase status:** `Done`

#### 06.0 — Kickoff

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |
| Docs | [PHASE_06_PAYROLL.md](./PHASE_06_PAYROLL.md) |

- [x] Branch `feature/phase-06-payroll`
- [x] PROGRESS expanded with steps 06.0–06.9

#### 06.1 — Permissions + role bundles

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Payroll keys in PermissionCatalog + seeder
- [x] Admin/HR/Manager/Employee role bundles updated

#### 06.2 — Schema + models

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Payroll tables migration
- [x] Models + factories

#### 06.3 — Compensation APIs

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Salary upsert + allowances/deductions/bonuses
- [x] Audit on salary change

#### 06.4 — Run lifecycle + calculation

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Create/calculate/approve/finalize
- [x] MonthlyPayrollStrategy + attendance/unpaid leave inputs

#### 06.5 — Payslips + PDF

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Payslip APIs + GeneratePayslipPdfJob

#### 06.6 — Feature tests

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] PayrollApiTest covering exit criteria

#### 06.7 — Frontend

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] API module + pages + nav + i18n

#### 06.8 — Seed

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] PayrollSeeder (non-prod) + DatabaseSeeder order

#### 06.9 — Phase exit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Exit criteria + suite green
- [x] Tag `v0.4.0` deferred until user asks
- [x] Snapshot → recommend Phase 07 next

**Notes:**

```
- Branch: feature/phase-06-payroll
- AttendanceSummaryService::summarizeForPayroll for service-to-service calc
- Calculate sync; PDF via GeneratePayslipPdfJob on payroll queue
- FE: /payroll, /payroll/{id}, /payroll/payslips, /payroll/compensation
- 111 Feature API tests + types:check green
- Tag v0.4.0 deferred
```

### Phase 07 — Recruitment & Ops

> Goal: Documents, assets, recruitment pipeline, onboarding → active employee.  
> Spec: [PHASE_07_RECRUITMENT.md](./PHASE_07_RECRUITMENT.md)

**Phase status:** `Done`

#### 07.0 — Kickoff

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |
| Docs | [PHASE_07_RECRUITMENT.md](./PHASE_07_RECRUITMENT.md) |

- [x] Branch `feature/phase-07-recruitment-ops`
- [x] PROGRESS expanded with steps 07.0–07.9

#### 07.1 — Permissions + role bundles

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Documents/Assets/Recruitment/Onboarding keys in PermissionCatalog + seeder
- [x] Admin/HR/Manager/Employee role bundles updated

#### 07.2 — Documents

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Documents migration + DocumentService APIs
- [x] DocumentApiTest

#### 07.3 — Assets

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Assets inventory + assign/return/damage/maintenance
- [x] AssetApiTest (assign/return audited)

#### 07.4 — Recruitment

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Job openings, candidates, interviews, offers
- [x] Offer accept handoff + RecruitmentApiTest

#### 07.5 — Onboarding

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Templates/cases/tasks + mandatory gate
- [x] OnboardingApiTest

#### 07.6 — Exit-criteria tests

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Cross-module HireOpsApiTest covering exit criteria

#### 07.7 — Frontend

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] API modules + pages + nav + i18n

**Notes:**

```
- API modules: documents, assets, recruitment, onboarding (lib/api/modules)
- Pages: /documents, /assets (+show), /recruitment (+candidates/show), /onboarding (+show)
- Nav: recruitment/onboarding/assets/documents (lucide icons) + permission-aware
- i18n: en/vi nav keys + documents/assets/recruitment/onboarding catalogs registered
- npm run types:check clean
```

#### 07.8 — Seed

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Document/Asset/Recruitment/Onboarding seeders + docs

**Notes:**

```
- Seeders (non-prod, refuse production): DocumentSeeder (company policy file via Storage),
  AssetSeeder (LAPTOP-0001 available), RecruitmentSeeder (JOB-0001 open + screening candidate),
  OnboardingSeeder (Default onboarding template: create_account/collect_docs/probation_ack mandatory,
  assign_equipment optional)
- Wired after PayrollSeeder in DatabaseSeeder; verified idempotent (counts stable on re-run)
- Docs: SEEDING.md Hire & ops row; DATABASE_NAMING.md onboarding_templates + onboarding_template_items
```

#### 07.9 — Phase exit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Exit criteria + suite green
- [x] Tag `v0.5.0` deferred until user asks
- [x] Snapshot → recommend Phase 08 next

**Notes:**

```
- Branch: feature/phase-07-recruitment-ops
- Offer accept → probation employee + onboarding case; mandatory gate; complete → active
- Documents private disk + authorized download; asset assign/return audited
- 138 Feature API tests + types:check green
- Tag v0.5.0 deferred
```

### Phase 08 — Insight

> Goal: Performance, reports/exports, notifications inbox, audit UX, dashboard KPIs.  
> Spec: [PHASE_08_PERFORMANCE.md](./PHASE_08_PERFORMANCE.md)

**Phase status:** `Done`

#### 08.0 — Kickoff

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |
| Docs | [PHASE_08_PERFORMANCE.md](./PHASE_08_PERFORMANCE.md) |

- [x] Branch `feature/phase-08-insight`
- [x] PROGRESS expanded with steps 08.0–08.9

#### 08.1 — Permissions + role bundles

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] PERFORMANCE/REPORTS/NOTIFICATIONS keys in PermissionCatalog + seeder
- [x] Admin/HR/Manager/Employee role bundles updated

#### 08.2 — Notifications

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Schema + NotificationService + inbox APIs
- [x] Replace Log* listeners; NotificationApiTest

#### 08.3 — Reports + export + dashboard

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Sync reports + CSV export job + dashboard summary
- [x] ReportApiTest

#### 08.4 — Performance

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Cycles/goals/evaluations/suggestions E2E APIs
- [x] PerformanceApiTest

#### 08.5 — Audit UX

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Filters/detail on audit-logs UI + API filters
- [x] Coverage assertions for critical actions

#### 08.6 — Exit-criteria tests

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] InsightApiTest covering PHASE_08 exit criteria

#### 08.7 — Frontend

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] API modules + pages + nav + i18n + dashboard KPIs

#### 08.8 — Seed

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Notification/Performance seeders + docs

#### 08.9 — Phase exit

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Exit criteria + suite green
- [x] Tag `v0.6.0` deferred until user asks
- [x] Snapshot → Future / v1.0.0 readiness discussion

**Notes:**

```
- Branch: feature/phase-08-insight
- Inbox wired from Leave/Payroll/HireOps events; CSV export 202→ready; dashboard KPIs
- Performance cycle draft→active→finalized; promotion suggestions advisory only
- Audit UI filters + detail panel; 160 Feature API tests + types:check green
- Tag v0.6.0 deferred
```

---

## Future backlog

### ∞.test-push-button — Admin send-test Web Push

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-09-05 |
| Completed | 2026-09-05 |
| Docs | [NOTIFICATION_API.md](../06-api/NOTIFICATION_API.md) |

- [x] `POST /api/notifications/test-push` (broadcast permission, sync send to current user)
- [x] Button on `/notifications`
- [x] i18n + tests

**Notes:**

```
- Immediate dispatchSync so cPanel queue drain delay does not hide failures
- Requires VAPID keys + this browser’s push subscription
```

### ∞.attendance-punch-reminders — Check-in/out reminders via Web Push

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-09-05 |
| Completed | 2026-09-05 |
| Docs | [notification/README.md](../02-business/notification/README.md), [NOTIFICATION_API.md](../06-api/NOTIFICATION_API.md), [DEPLOYMENT.md](../08-development/DEPLOYMENT.md) |

- [x] Laravel scheduler command for missed check-in/out (company timezone, grace minutes)
- [x] Idempotent `attendance_punch_reminders` + inbox/email
- [x] Web Push VAPID subscriptions (no Firebase); cPanel cron + queue drain
- [x] Attendance enable banner + Service Worker; i18n en/vi
- [x] Tests + deployment notes

**Notes:**

```
- Channel: Web Push (VAPID) + existing inbox/email
- cPanel: * * * * * php artisan schedule:run
- Check-in reminders: today only; check-out: today + overnight night shifts
```

### ∞.schedule-off-hover-label — Scheduled-off calendar hover label

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-09-05 |
| Completed | 2026-09-05 |
| Docs | — |

- [x] Month calendar tooltip uses restLabel (off vs weekend vs holiday)

**Notes:**

```
- Hover on rest_kind=off showed Weekend because tooltip treated every non-holiday rest as weekend
```

### ∞.shift-assign-form-polish — Shift assignment form polish

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-09-05 |
| Completed | 2026-09-05 |
| Docs | — |

- [x] Compact card layout + side list on wide screens
- [x] Larger weekday grid + presets (weekdays / MWF)
- [x] Clearer dual-session callout; types:check

**Notes:**

```
- Form card + assigned list side-by-side from xl; weekday 7-col grid; Mon–Fri and MWF presets
- Dual-session callout links to /shifts; tsc --noEmit green
```

### ∞.shift-assign-form-ux — Shift assignment form UX

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-09-05 |
| Completed | 2026-09-05 |
| Docs | — |

- [x] Stacked assign form on `/shifts/{id}` (weekdays inside form)
- [x] Mode toggle + weekday chips always visible
- [x] EN/VI copy + assignment-list badges
- [x] types:check + browser check

**Notes:**

```
- Assign form is stacked; weekday chips sit between dates and submit
- Mode: selected days vs every day in range (chips stay visible, disabled when every day)
- Dual-session hint is on the shift summary, not buried under the old checkbox row
- tsc --noEmit green; IDE browser MCP unavailable — confirm Assign UI in the running app
```

### ∞.flexible-parttime-assignments — Flexible part-time shift assignment

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-09-05 |
| Completed | 2026-09-05 |
| Docs | [SHIFT_API.md](../06-api/SHIFT_API.md), [ATTENDANCE_API.md](../06-api/ATTENDANCE_API.md), [shift/README.md](../02-business/shift/README.md) |

- [x] Assignment `weekdays` + overlap by date ∩ weekday ∩ time
- [x] Working calendar multi-window + `rest_kind=off`
- [x] Attendance `shift_id` (one record per shift per day) + punch match
- [x] Leave duration / AM-PM vs windows; `days_present` distinct dates
- [x] Admin weekday picker + PT shift seed
- [x] My Schedule + today card multi-session
- [x] Tests + types:check

**Notes:**

```
- Full-time: omit weekdays = every day in range (backward compatible)
- Same calendar day: two non-overlapping shifts → two punch records
- Unique attendance: (company_id, employee_id, work_date, shift_id)
- ShiftApiTest + AttendanceApiTest + Leave/Payroll related tests green; tsc --noEmit clean
```

### ∞.date-time-pickers — Shared date/time pickers

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Wave 0: popover/calendar primitives, `date-fns` + `react-day-picker`, shared pickers + `lib/datetime` + common i18n
- [x] Wave 1: Leave, Holidays, Attendance corrections, Shifts, My Schedule
- [x] Wave 2: Payroll, Reports, Audit, Performance date filters
- [x] Wave 3: Employees create + Recruitment interview datetime
- [x] types:check green

**Notes:**

```
- Deps: react-day-picker@9.8.0, date-fns, @radix-ui/react-popover
- ui/: popover.tsx, calendar.tsx
- shared/: date-picker, date-range-picker, time-picker, date-time-picker
- lib/datetime.ts: parse/format YYYY-MM-DD, HH:mm, datetime-local + vi/en display
- common.date_picker.* i18n (en/vi); migrated all former native date/time inputs
```

### ∞.employee-picker — Shared employee picker dialog

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Shared `EmployeePickerDialog` + `EmployeePickerField`
- [x] i18n keys in common (en/vi)
- [x] Wire into onboarding, shifts, assets, performance, payroll compensation, documents
- [x] Wire into roles assign (employee → linked `user_id`)
- [x] types:check green

**Notes:**

```
- Components: resources/js/components/shared/employee-picker-{dialog,field}.tsx
- Dialog: search debounce, status + department filters via listEmployees + org tree
- Wired: onboarding, shifts/show, assets/show, performance/cycles/show, payroll/compensation, documents (employee owner), roles assign
- Roles: select employee then sync `/api/users/{user_id}/roles`; toast if employee has no linked user
- Candidate owner_id unchanged
```

### ∞.i18n-audit-roles — Audit logs + Roles i18n polish

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Permission catalog parity (performance / reports / notifications) in en+vi
- [x] System role display names by locale
- [x] Audit action + subject labels; audit-logs UI helper
- [x] I18N.md conventions updated

**Notes:**

```
- permissions.json: 78 keys = PermissionCatalog::allKeys()
- audit.actions.* covers all AuditLogger action codes; subjects.* by model basename
- Helper: resources/js/lib/i18n/audit-labels.ts; wired on /audit-logs
- Filter form: localized Select for action (grouped) + subject morph; date range unchanged
- roles:system_roles.* for admin/hr/manager/employee; types:check green
```

### ∞.employee-default-password — Default password + HR password management

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] `EMPLOYEE_DEFAULT_PASSWORD` in `.env.example` + `config/hrm.php`
- [x] Onboarding `create_account` uses default password (not random)
- [x] `PUT /api/employees/{id}/password` (custom or `use_default`)
- [x] Employee show UI: set password + reset to default
- [x] Feature tests + EMPLOYEE_API.md

**Notes:**

```
- EmployeeAccountService: provision / setPassword / resetToDefault
- Onboarding create_account → default from config('hrm.employee_default_password')
- PUT /api/employees/{id}/password; can_update_employee; EMPLOYEE_NO_USER_ACCOUNT
- Employee show: password section when user_id set; i18n en/vi + audit labels
- phpunit.xml EMPLOYEE_DEFAULT_PASSWORD=password; Employee+Onboarding API tests green; types:check green
```

### ∞.my-schedule-ux — My Schedule table + calendar views

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] View toggle: Calendar (default) + Table; preference in localStorage
- [x] Improved table: localized dates, today highlight, holiday badge
- [x] Month calendar grid from working-calendar API
- [x] i18n en/vi for new labels
- [x] types:check green

**Notes:**

```
- Components: schedule-toolbar, schedule-table, schedule-month-calendar, schedule-view
- Default view Calendar; localStorage key hrm.my-schedule.view
- Calendar: month nav + today; Table: DateRangePicker + search
- Holiday badge + today highlight; tooltip on calendar shift cells
- No backend changes; types:check green
```

### ∞.attendance-ux — Attendance today-first UX polish

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Today status card + check-in/out CTAs
- [x] Month summary strip via getSummary
- [x] Records table: localized dates, durations, badges, date range
- [x] Corrections: record select + clearer list + query prefill
- [x] i18n en/vi + types:check green

**Notes:**

```
- Components: today-status-card, month-summary-strip, attendance-records-table, status-badge, format-minutes
- Index: today punch hero (independent of filter), summary strip, DateRangePicker default current month
- Corrections: Select recent records; ?record_id= prefill; card list with badges
- No backend changes; types:check green
```

### ∞.notification-header-bell — Header notification bell + unread badge

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Bell icon in AppSidebarHeader (right side)
- [x] Red badge with unread count via unread-count API
- [x] Permission-gated; poll / focus refresh
- [x] i18n + types:check green

**Notes:**

```
- Component: resources/js/components/shared/notification-bell.tsx
- Wired in AppSidebarHeader next to LanguageSwitcher
- Red destructive badge; 99+ cap; poll 60s + window refresh
- Permission: can_view_own_notifications; types:check green
```

### ∞.notification-header-panel — Header notification dropdown panel

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Bell opens dropdown panel (not navigate away)
- [x] Recent list + mark read / mark all
- [x] Footer link to full notifications page
- [x] i18n + types:check green

**Notes:**

```
- Dropdown under bell: recent 8 items, mark read on click, mark all read
- Footer CTA "see_earlier" → /notifications
- Unread red badge retained; poll 60s + focus; types:check green
```

### ∞.attendance-datetime-tz — Attendance correction timezone fix

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Naive datetime-local interpreted as company timezone (not UTC)
- [x] Frontend `toApiDateTime` sends ISO UTC from local wall clock
- [x] Corrections + recruitment interview wired
- [x] Feature test for naive correction timestamps
- [x] types:check / attendance tests green

**Notes:**

```
- Bug: DateTimePicker sent YYYY-MM-DDTHH:mm without offset; app TZ UTC
  treated wall clock as UTC; list showed browser local (+7) → 08:00→15:00
- FE: toApiDateTime + formatPunchTime in lib/datetime.ts; corrections + interview
- BE: parseWorkedAt naive → company TZ then →utc(); work_date + metrics use company TZ
- Test: naive correction stores 01:00/10:00 UTC (= 08:00/17:00 ICT)
```

### ∞.attendance-record-employee — Show employee on attendance records

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Employee column on records table (code + name)
- [x] Corrections select/list show employee
- [x] Managers list without forced own employee_id filter
- [x] i18n + types:check green

**Notes:**

```
- Table col: employee.full_name + code (API already eager-loads employee)
- Managers (approve/manage) list all company records; employees stay own-scoped
- Corrections dropdown + cards show employee; types:check green
```

### ∞.attendance-correction-badge — Pending correction badge on records

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-18 |
| Completed | 2026-07-18 |

- [x] Load pending corrections on attendance index
- [x] Red count badge per record row linking to corrections
- [x] Visible to approvers (not only requestors)
- [x] i18n + types:check green

**Notes:**

```
- Index loads listCorrections(status=pending) alongside records
- Aggregates counts by attendance_record_id → red badge on row action
- Label switches to "Corrections"/"Hiệu chỉnh" when pending > 0
- Approvers see action via can_approve_attendance; types:check green
```

### ∞.roles-permission-tabs — Group permissions into tabs on /roles

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] Group permission checkboxes by catalog `group` into tabs
- [x] Badge selected/total per tab; select-all / clear in active tab
- [x] en/vi i18n for group labels
- [x] types:check green

**Notes:**

```
- Frontend-only: roles/index.tsx groups by Permission.group
- Scrollable custom tabs + selected/total badge; select-all / clear per tab
- i18n: roles.permission_groups.* + select_all_group / clear_group (en/vi)
- types:check green
```

### ∞.currency-format — VND/USD money display + input mask

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] `lib/currency.ts`: normalize, formatCurrency, parse/formatMoneyInput
- [x] Shared `CurrencyInput` + `CurrencySelect` (VND/USD)
- [x] Payroll index/show/payslips display via company currency
- [x] Compensation + offer create/display wired; types:check green

**Notes:**

```
- Display: Intl currency (vi-VN / en-US); payroll pages load company.currency
- Input: masked grouping without symbol; API still receives raw numbers
- Offer create now sends currency; list uses formatCurrency(amount, currency)
```

### ∞.duration-format — Readable hour/minute duration labels (vi/en)

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] `duration.hours` / `duration.minutes` i18n keys (en + vi)
- [x] `formatDuration(minutes, t)` full labels
- [x] Wire records table, today card, month strip
- [x] types:check green

**Notes:**

```
- Replace abbreviated 1h 30m with 1 tiếng 30 phút / 1 hour 30 minutes
- EN plural via i18next _one/_other; VI invariant tiếng/phút
```

### ∞.assets-create-dialog — Assets list table-first + create dialog

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] Optional `actions` slot on `AdminPageShell`
- [x] Assets index: create form in Dialog; top-right Create button
- [x] i18n `create_description` (en + vi)
- [x] types:check green

**Notes:**

```
- AdminPageShell actions → header top-right Create button (can_manage_assets)
- Dialog: code/name/category/serial; reset on close; reload list on success
- Table chrome aligned with employees list (border + muted thead)
```

### ∞.employees-create-dialog — Employees list table-first + create dialog

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] Employees index: Create button in `AdminPageShell` actions
- [x] Create form in Dialog (same fields as create page)
- [x] Reset on close; reload list on success
- [x] types:check green

**Notes:**

```
- AdminPageShell actions → Create employee (can_create_employee)
- Dialog: code/status/names/email/phone/hired_at; reuse create.* i18n
- Primary UX via dialog; /employees/create route kept for deep links
```

### ∞.shifts-create-dialog — Shifts list table-first + create dialog

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] Shifts index: Create button in `AdminPageShell` actions
- [x] Create form in Dialog (same fields as create page)
- [x] Reset on close; reload list on success
- [x] types:check green

**Notes:**

```
- AdminPageShell actions → Create shift (can_manage_shift_definitions)
- Dialog: code/name/times/break/kind/flags; reuse create.* i18n
- Primary UX via dialog; /shifts/create route kept for deep links
```

### ∞.list-create-dialogs — Payroll / Performance / Recruitment / Documents create dialogs

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] Payroll: create run dialog; payslips/compensation links in header actions
- [x] Performance: create review cycle dialog
- [x] Recruitment: create opening + create candidate dialogs
- [x] Documents: upload dialog
- [x] types:check green

**Notes:**

```
- Same table-first pattern as assets/employees/shifts
- Inline create/upload forms removed; tables + filters remain primary
```

### ∞.organization-ux — Organization tree-first UX redesign

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] Tree-first layout with selection detail panel
- [x] Type badges, child counts, collapsible branches/departments
- [x] Dialog create/edit/delete; header Add menu; contextual Add child
- [x] Company edit dialog; positions table section
- [x] en/vi i18n; types:check green

**Notes:**

```
- /organization: company strip + collapsible tree | detail panel + positions table
- Components under resources/js/pages/organization/; dialogs replace prompt/confirm
- Header Add menu + contextual Add department/team from selected node
```

### ∞.report-format — Format duration + currency on Reports

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] `reportCellLabel` formats minute + money columns
- [x] Reports page loads company currency; attendance ns for duration
- [x] CSV download uses the same formatted cells
- [x] Column labels drop "(minutes)"; types:check green

**Notes:**

```
- Attendance: late/overtime/worked_minutes → formatDuration
- Payroll: total_gross/total_net → formatCurrency(company.currency)
- Follow-up: cell i18n uses appliedReport (not live select); clear results on type change;
  leave_type via leave_type_code + reports:leave_types.*; dates locale-formatted
```

### ∞.leave-create-dialogs — Leave list table-first + create dialogs

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] Leave index: request dialog; types/holidays links in header actions
- [x] Leave types: create type dialog + table list
- [x] Holidays: add holiday dialog; weekend rules stay on page
- [x] types:check green

**Notes:**

```
- Same table-first pattern as other list pages
- Weekend rules section kept inline (settings, not create form)
```

### ∞.payslips-ux — Payslips list + readable detail sheet

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] Table: period locale format, net emphasis, PDF status, compact actions
- [x] Detail Sheet: summary + earnings/deductions (no raw JSON)
- [x] Manager employee filter via `listPayslips({ employee_id })`
- [x] i18n for section/type/system labels (vi/en)
- [x] types:check green

**Notes:**

```
- FE: payslips.tsx Dialog detail; payslip-components helper for group/localize
- List: locale period, PDF badge (has_pdf), View + Download actions
- Manager filter: EmployeePickerField → employee_id
- i18n: detail_title, empty earnings/deductions, PDF status, ns:payroll labels
```

### ∞.payroll-run-edit-delete — Update & delete payroll runs

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] `PUT /api/payroll-runs/{id}` — name/period when draft only
- [x] `DELETE /api/payroll-runs/{id}` — before finalized
- [x] Policy, audits, feature tests
- [x] Show UI edit dialog + delete; i18n
- [x] PAYROLL_API.md updated

**Notes:**

```
- Update draft-only (PAYROLL_NOT_DRAFT); delete draft/calculated/approved
- Audits: payroll.run_updated / payroll.run_deleted
- FE: show edit dialog + delete; index row delete for non-finalized
- PayrollApiTest + types:check green
```

### ∞.leave-schedule-attendance-payroll — Approved leave on calendar, attendance, payroll

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-19 |
| Completed | 2026-07-19 |

- [x] `LeaveCoverageService` — day/half/hour coverage + unpaid day-equivalent proration
- [x] Working calendar API overlays approved leave (keeps shift fields)
- [x] Attendance metrics respect leave windows (full / half / hours)
- [x] Payroll uses LeaveCoverageService (no direct LeaveRequest Eloquent)
- [x] My Schedule UI legend + leave badges; i18n en/vi
- [x] Tests: LeaveCoverageService, Shift, Attendance, Payroll

**Notes:**

```
- No Shift→Leave dependency in WorkingCalendarService; merge in controller
- Paid leave never deducted; unpaid prorated across payroll periods
- Docs: SHIFT_API, leave/attendance business READMEs
```

### ∞.sanctum-prod-session — Production SPA login session store

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |

- [x] `config/sanctum.php` always merges `APP_URL` host into stateful domains
- [x] `.env.example` + `DEPLOYMENT.md` document prod Sanctum / CORS / session
- [x] AuthApiTest regression for production Origin + APP_URL host assert
- [x] Document production `.env` checklist for deployers

**Notes:**

```
- Symptom: RuntimeException "Session store not set on request" on POST /api/auth/login
- Cause: SANCTUM_STATEFUL_DOMAINS left as localhost-only → Sanctum skips StartSession
- Fix: always merge APP_URL host; set APP_URL + clear config cache on prod
```

### ∞.richer-dashboard — Stitch-aligned richer dashboard

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [REPORT_API.md](../06-api/REPORT_API.md), [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md) |

- [x] Expand `GET /api/dashboard/summary` (KPIs, series, lists)
- [x] Frontend: Recharts + widgets (attendance, headcount, pending, upcoming, hires, activity)
- [x] i18n en/vi + types
- [x] ReportApiTest + types:check green

**Notes:**

```
- DashboardService: attendance rate, 7-day series, status/dept breakdowns, recent hires,
  pending actions, upcoming holidays/leave, notification activity
- FE: recharts BarChart + PieChart; components under pages/dashboard/
- ReportApiTest + InsightApiTest dashboard keys; types:check clean
```

### ∞.dashboard-role-views — Company vs employee dashboard

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [REPORT_API.md](../06-api/REPORT_API.md) |

- [x] `scope: company|self` on dashboard summary (`can_view_employee_reports` gate)
- [x] Self view: own attendance, leave balances, upcoming, activity
- [x] FE branch + i18n; tests green

**Notes:**

```
- Gate: can_view_employee_reports → company overview; else → self
- Self: today punch, leave balances, 7-day personal chart, own upcoming/actions
- ReportApiTest company + self; InsightApiTest; types:check clean
```

### ∞.branding-plt-hrm — Product brand HRM + PLT Solutions

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | — |

- [x] App logo two-line brand + HR monogram icon
- [x] Auth layouts + welcome landing rebranded
- [x] Remove starter-kit header links; APP_NAME / composer defaults
- [x] types:check clean

**Notes:**

```
- Product HRM + company PLT Solutions (lib/brand.ts); APP_NAME=HRM defaults
- Welcome landing simplified; auth layouts show brand; starter-kit header links removed
- composer: plt-solutions/jindo-plt-hrm; types:check clean
```

### ∞.employee-avatar — Employee avatar upload & display

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [EMPLOYEE_API.md](../06-api/EMPLOYEE_API.md), [DEPLOYMENT.md](../08-development/DEPLOYMENT.md) |

- [x] `employees.avatar_path` + public-disk storage service
- [x] POST/DELETE `/api/employees/{id}/avatar` and `/api/me/avatar`
- [x] Expose `avatar_url` / auth `avatar`; Settings + employee list/show UI
- [x] Feature tests + `storage:link` note

**Notes:**

```
- Employee source of truth; auth.user.avatar from linked employee
- Public disk opaque paths; self or can_update_employee
- EmployeeApiTest avatar cases + AuthApiTest structure; types:check clean
```

### ∞.welcome-landing — Full-bleed welcome hero

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | — |

- [x] Local hero image at `public/images/welcome-hero.jpg`
- [x] Full-bleed welcome page with brand + CTA + scrim
- [x] en/vi `welcome` i18n namespace
- [x] types:check clean

**Notes:**

```
- Unsplash workplace photo (photo-1522071820081) stored locally; no CDN hotlink
- Single-composition login gate; LanguageSwitcher on hero header
```

### ∞.disable-self-delete-account — No self-service account deletion

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | — |

- [x] Remove Delete account UI from Settings → Profile
- [x] Remove `profile.destroy` self-delete endpoint
- [x] Update ProfileUpdateTest; employee archive stays permission-gated

**Notes:**

```
- Employees cannot delete their own login account (UI + route removed)
- Employee archive remains behind can_change_employee_status (admin/HR)
```

### ∞.attendance-evidence — GPS + camera evidence for punches

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [ATTENDANCE_API.md](../06-api/ATTENDANCE_API.md), [attendance/README.md](../02-business/attendance/README.md), [FILE_STORAGE.md](../01-architecture/FILE_STORAGE.md) |

- [x] `attendance_evidences` migration + model (lat/lng/address/photo per punch)
- [x] Check-in/out require multipart evidence; reject without write (`ATTENDANCE_EVIDENCE_REQUIRED`)
- [x] Private photo storage + authorized download endpoint
- [x] Punch evidence dialog (geolocation + reverse geocode + camera)
- [x] Show address/photo on today card + records table; en/vi i18n
- [x] AttendanceApiTest + types:check green

**Notes:**

```
- Location policy: record only (no geofence)
- Photo: evidence capture only (no face AI)
- Required for both check-in and check-out
```

### ∞.performance-cycle-ux — Complete review cycle UX

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [PERFORMANCE_API.md](../06-api/PERFORMANCE_API.md), [performance/README.md](../02-business/performance/README.md) |

- [x] Sync participants on draft cycles; start requires ≥1 participant
- [x] Goal scoped to cycle participants; cycle progress counts; promotion filter by cycle
- [x] Create dialog + cycle show: participants, goal progress, scoped pickers, finalize warn
- [x] en/vi i18n + PerformanceApiTest + types:check

**Notes:**

```
- Participants are the center of the cycle workflow
- Finalize warns when evaluations incomplete (no hard block)
- UX polish: status badges, progress meters, clearer cycle list/detail hierarchy
- Non-participants cannot view cycles; removing a participant deletes their goals; draft cycles can be deleted
```

### ∞.my-schedule-calendar-info — Attendance + rest-day clarity on My Schedule

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [SHIFT_API.md](../06-api/SHIFT_API.md) |

- [x] Working-calendar: `rest_kind`, `holiday_name`, rest-only days (keep `resolve()` for domain)
- [x] FE: merge attendance records + month summary on My Schedule
- [x] Calendar/table cells: weekend vs holiday vs leave vs punch results
- [x] i18n en/vi + ShiftApiTest + types:check

**Notes:**

```
- API: rest_kind none|weekend|holiday; holiday_name; rest-only rows with null shift fields
- resolve() still assigned-only for Attendance/Leave; controller merges unassignedRestDays
- FE: listRecords + getSummary best-effort; calendar cells show in/out + late; link to /attendance
- Backlog: day detail sheet, absent marker, pending leave style, legend filters, week/agenda, ICS
```

### ∞.production-seed — Production bootstrap seeder

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [SEEDING.md](../03-database/SEEDING.md), [DEPLOYMENT.md](../08-development/DEPLOYMENT.md) |

- [x] `ProductionBootstrapSeeder`: permissions, roles, company, settings, shifts+OT, admin
- [x] Env: `SEED_COMPANY_*` (defaults) + required `SEED_ADMIN_*`
- [x] `DatabaseSeeder` production → bootstrap only; local demo unchanged
- [x] Docs + Feature test

**Notes:**

```
- Shared SeedsShiftDefinitions trait for MORNING/NIGHT/STANDARD
- config/hrm.php seed.*; .env.example commented SEED_* vars
- ProductionBootstrapSeederTest: missing admin throws; happy path + idempotent
```

### ∞.employee-org-placement — Assign department & position on employees

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [EMPLOYEE_API.md](../06-api/EMPLOYEE_API.md) |

- [x] Shared `EmployeeOrgPlacementFields` from org tree
- [x] Create page + list create dialog send `department_id` / `position_id`
- [x] Show profile edit under `can_update_employee`
- [x] i18n en/vi + PATCH API assertion

**Notes:**

```
- Flat department + position selects via getOrganizationTree()
- Read-only org names on show when lacking can_update_employee
- EmployeeApiTest asserts create/PATCH department_id + position_id
```

### ∞.my-schedule-mobile — Hybrid mobile layout for My Schedule

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [RESPONSIVE.md](../07-uiux/RESPONSIVE.md) |

- [x] Compact month grid on mobile (no horizontal scroll)
- [x] Agenda list for remaining days in visible month
- [x] Bottom Sheet for day detail (calendar + agenda + table cards)
- [x] Mobile card list for table view
- [x] Toolbar + AdminPageShell mobile padding polish
- [x] i18n en/vi + types:check

**Notes:**

```
- Hybrid: compact calendar + upcoming agenda; desktop keeps rich calendar/table
- Components: schedule-day-helpers, schedule-day-sheet, schedule-agenda-list
- Breakpoint: useIsMobile (<768px); AdminPageShell card p-4 md:p-6
- types:check green
```

### ∞.attendance-mobile — Mobile layout for Attendance index

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [RESPONSIVE.md](../07-uiux/RESPONSIVE.md) |

- [x] TodayStatusCard punch-first full-width CTAs on mobile
- [x] PunchEvidenceDialog: bottom Sheet on mobile
- [x] Mobile records card list + AttendanceRecordSheet
- [x] Period filter chip scroll + full-width DateRangePicker
- [x] i18n en/vi + types:check

**Notes:**

```
- Scope: /attendance index; punch Sheet + record detail Sheet on mobile
- Components: attendance-record-sheet; records table branches via useIsMobile
- Period chips overflow-x-auto; desktop Dialog/table unchanged
- types:check green
```

### ∞.leave-mobile — Mobile layout for Leave index

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [RESPONSIVE.md](../07-uiux/RESPONSIVE.md) |

- [x] Submit CTA primary on mobile; Types/Holidays secondary
- [x] Create request: Sheet on mobile, Dialog on desktop
- [x] Request card list + LeaveRequestSheet (approve/reject/cancel)
- [x] i18n en/vi + types:check

**Notes:**

```
- Scope: /leave index; types/holidays out of scope
- Components: leave-request-form, leave-request-create, leave-requests-table, leave-request-sheet
- Mobile: full-width Submit + card list + detail Sheet; desktop table/Dialog unchanged
- types:check green
```

### ∞.payslips-performance-notifications-mobile — Mobile layouts for payslips, performance, notifications

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [RESPONSIVE.md](../07-uiux/RESPONSIVE.md) |

- [x] `/payroll/payslips`: card list + detail Sheet on mobile
- [x] `/performance`: card list + create Sheet on mobile
- [x] `/notifications`: toolbar + card action polish on mobile
- [x] types:check

**Notes:**

```
- Payslips: cards + bottom Sheet detail; desktop table/Dialog unchanged
- Performance index: full-width create CTA + cards + create Sheet; cycle show out of scope
- Notifications: stacked toolbar + full-width row actions; already card list
- types:check green
```

### ∞.assets-documents-mobile — Mobile layouts for Assets and Documents

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [RESPONSIVE.md](../07-uiux/RESPONSIVE.md) |

- [x] `/assets`: card list + create Sheet on mobile
- [x] `/documents`: card list + upload Sheet on mobile
- [x] types:check

**Notes:**

```
- Assets: full-width Create CTA + cards + create Sheet; desktop table/Dialog unchanged
- Documents: full-width Upload CTA + cards + upload Sheet; filters stack on mobile
- Asset show page out of scope
- types:check green
```

### ∞.dashboard-mobile — Mobile polish for company + self dashboard

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-26 |
| Completed | 2026-07-26 |
| Docs | [RESPONSIVE.md](../07-uiux/RESPONSIVE.md) |

- [x] KPI grids 2-col on mobile; denser card padding
- [x] Self quick links full-width; pending actions earlier on mobile
- [x] Charts + panel tap targets polish
- [x] types:check

**Notes:**

```
- Company + self: pending/upcoming/activity order-1 on mobile, charts order-2; lg restores side column
- Self: Attendance/Leave/Schedule links full-width min-h-11
- Charts h-52 sm:h-64; panels p-4 sm:p-5
- types:check green
```

### ∞.attendance-punch-resilience — Check-in/out infra + offline resilience

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-27 |
| Completed | 2026-07-27 |
| Docs | [ATTENDANCE_API.md](../06-api/ATTENDANCE_API.md), [ERROR_HANDLING.md](../04-backend/ERROR_HANDLING.md), [API_CLIENT.md](../05-frontend/API_CLIENT.md) |

- [x] Map 503/502 to stable `error_code`; require `Idempotency-Key` on punch
- [x] Persist punch idempotency rows (48h TTL); safe replay
- [x] FE: classify infra errors, client timeout, i18n toasts
- [x] IndexedDB offline queue + sync banner on `/attendance`
- [x] AttendanceApiTest + types:check

**Notes:**

```
- Scope: UX + Idempotency-Key + offline queue (no Service Worker)
- Migration: attendance_punch_idempotency; AttendancePunchIdempotencyService
- FE: punch-queue.ts (IndexedDB), classifyPunchError, pending sync banner
- AttendanceApiTest 25 passed; types:check green
```

### ∞.attendance-punch-load-retry — GPS load retry before punch (max 5)

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-07-27 |
| Completed | 2026-07-27 |
| Docs | — |

- [x] `getCurrentPunchLocationWithRetry` (max 5, backoff, skip permission/unsupported)
- [x] PunchEvidenceDialog boot + Retry location use retry helper; attempt UI
- [x] en/vi i18n + types:check

**Notes:**

```
- Scope: GPS evidence load only (not camera permission, not API list load)
- Backoff 500ms → 1s → 2s → 3s; attempt UI evidence.location_loading_attempt
- types:check green
```

### ∞.prod-log-20260728-attendance-checkout — Production log triage (Jul 28–30)

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-08-03 |
| Completed | 2026-08-03 |
| Docs | [ERROR_HANDLING.md](../04-backend/ERROR_HANDLING.md), [DEPLOYMENT.md](../08-development/DEPLOYMENT.md) |

- [x] `DomainException` dontReport (stop ERROR noise + password in stacktraces)
- [x] Check-out resolves open session across midnight / `captured_at` punch time
- [x] FE: send `worked_at` + refresh/fallback open yesterday
- [x] Ops checklist: `APP_ENV=production`, MySQL uptime
- [x] Tests + types:check

**Notes:**

```
- Source: storage/production_logs/03082026_laravel.log
- bootstrap/app.php: dontReport DomainException
- AttendanceService: resolvePunchAt + findCheckOutTargetRecord (1-day lookback)
- FE: postPunch worked_at←captured_at; loadToday yesterday open; day-rollover refresh
- DEPLOYMENT.md ops checklist; Attendance+Auth API tests 41 passed; types:check green
```

### ∞.attendance-bulk-approve — Bulk approve selected attendance records

| Field | Value |
|-------|--------|
| Status | `Done` |
| Started | 2026-08-04 |
| Completed | 2026-08-04 |
| Docs | [ATTENDANCE_API.md](../06-api/ATTENDANCE_API.md), [TABLE_GUIDELINE.md](../05-frontend/TABLE_GUIDELINE.md) |

- [x] `POST /api/attendance/records/bulk-approve` + service + tests
- [x] FE API client `bulkApproveRecords`
- [x] Checkbox selection + select-all pending + bulk action bar
- [x] Confirm dialog + correction warning + i18n (en/vi)
- [x] Docs + types:check

**Notes:**

```
- AttendanceService::approveRecords (max 100 ids, skip non-pending/foreign)
- FE: checkboxes desktop+mobile, select-all pending, confirm when ≥2
- Tests: bulk approve / skip / permission; types:check green
```

---

## Activity log

> Append-only. Newest on top. One row per meaningful status change.

| Date | Step | Change | By |
|------|------|--------|-----|
| 2026-09-05 | `∞.test-push-button` | Done: admin test Web Push button + sync API | agent |
| 2026-09-05 | `∞.test-push-button` | Started: admin immediate test Web Push | agent |
| 2026-09-05 | `∞.attendance-punch-reminders` | Done: scheduler punch reminders + Web Push VAPID; tests green | agent |
| 2026-09-05 | `∞.attendance-punch-reminders` | Started: Web Push VAPID punch reminders for cPanel | agent |
| 2026-09-05 | `∞.schedule-off-hover-label` | Done: scheduled-off hover uses Nghỉ theo lịch, not Weekend | agent |
| 2026-09-05 | `∞.shift-assign-form-polish` | Done: compact assign card, weekday grid + presets; types green | agent |
| 2026-09-05 | `∞.shift-assign-form-ux` | Done: stacked assign form with weekday chips in-form; types green | agent |
| 2026-09-05 | `∞.flexible-parttime-assignments` | Done: weekday mask + same-day multi-session punch; tests + types green | agent |
| 2026-09-05 | `∞.flexible-parttime-assignments` | Started: weekday mask + same-day multi-session shifts | agent |
| 2026-08-04 | `∞.attendance-bulk-approve` | Done: bulk-approve API + checkbox selection UI; tests + types green | agent |
| 2026-08-04 | `∞.attendance-bulk-approve` | Started: bulk-approve API + checkbox selection UI | agent |
| 2026-08-03 | `∞.prod-log-20260728-attendance-checkout` | Done: DomainException dontReport + post-midnight checkout + FE; tests green | agent |
| 2026-08-03 | `∞.prod-log-20260728-attendance-checkout` | Started: fix DomainException logging + post-midnight checkout | agent |
| 2026-07-27 | `∞.attendance-punch-load-retry` | Done: GPS load retry max 5 + attempt UI; types green | agent |
| 2026-07-27 | `∞.attendance-punch-load-retry` | Started: GPS location load retry max 5 for punch dialog | agent |
| 2026-07-27 | `∞.attendance-punch-resilience` | Done: Idempotency-Key + offline queue + 503 UX; tests + types green | agent |
| 2026-07-27 | `∞.attendance-punch-resilience` | Started: punch 503/offline resilience + Idempotency-Key | agent |
| 2026-07-26 | `∞.dashboard-mobile` | Done: company+self dashboard mobile polish; types green | agent |
| 2026-07-26 | `∞.dashboard-mobile` | Started: mobile polish for company + self dashboard | agent |
| 2026-07-26 | `∞.assets-documents-mobile` | Done: assets + documents mobile cards/Sheets; types green | agent |
| 2026-07-26 | `∞.assets-documents-mobile` | Started: mobile layouts for Assets and Documents | agent |
| 2026-07-26 | `∞.payslips-performance-notifications-mobile` | Done: payslips/performance/notifications mobile; types green | agent |
| 2026-07-26 | `∞.payslips-performance-notifications-mobile` | Started: mobile layouts for payslips, performance, notifications | agent |
| 2026-07-26 | `∞.leave-mobile` | Done: create Sheet + request cards + detail Sheet; types green | agent |
| 2026-07-26 | `∞.leave-mobile` | Started: mobile layout for Leave index | agent |
| 2026-07-26 | `∞.attendance-mobile` | Done: punch Sheet + records cards + day sheet; types green | agent |
| 2026-07-26 | `∞.attendance-mobile` | Started: mobile layout for Attendance index | agent |
| 2026-07-26 | `∞.my-schedule-mobile` | Done: hybrid mobile calendar + agenda + day sheet; types green | agent |
| 2026-07-26 | `∞.my-schedule-mobile` | Started: hybrid mobile layout for My Schedule | agent |
| 2026-07-26 | `∞.employee-org-placement` | Done: dept/position UI on create + show; EmployeeApiTest + types green | agent |
| 2026-07-26 | `∞.employee-org-placement` | Started: department/position assignment UI on employee create + show | agent |
| 2026-07-26 | `∞.production-seed` | Follow-up: Makefile `seed-local` / `seed-admin` targets | agent |
| 2026-07-26 | `∞.production-seed` | Done: ProductionBootstrapSeeder + env admin/company; tests green | agent |
| 2026-07-26 | `∞.production-seed` | Started: production bootstrap seeder (admin, roles, company, settings, shifts) | agent |
| 2026-07-26 | `∞.my-schedule-calendar-info` | Done: rest_kind/holiday + attendance on calendar; ShiftApiTest + types green | agent |
| 2026-07-26 | `∞.my-schedule-calendar-info` | Started: attendance + rest-day clarity on My Schedule calendar | agent |
| 2026-07-26 | `∞.performance-cycle-ux` | Scope view + cascade goals on remove + delete draft; tests green | agent |
| 2026-07-26 | `∞.performance-cycle-ux` | UX polish: badges, progress meters, list/detail hierarchy; types green | agent |
| 2026-07-26 | `∞.performance-cycle-ux` | Done: cycle participants + goal progress UX; tests + types green | agent |
| 2026-07-26 | `∞.performance-cycle-ux` | Started: complete review cycle UX + participant guards | agent |
| 2026-07-26 | `∞.attendance-evidence` | Done: GPS+camera evidence gate; tests + types green | agent |
| 2026-07-26 | `∞.attendance-evidence` | Started: require location + photo for check-in/out | agent |
| 2026-07-26 | `∞.disable-self-delete-account` | Done: removed self-delete UI/route; ProfileUpdateTest green | agent |
| 2026-07-26 | `∞.disable-self-delete-account` | Started: block self-service account deletion | agent |
| 2026-07-26 | `∞.employee-avatar` | Done: avatar upload/display API + UI; tests + types green | agent |
| 2026-07-26 | `∞.welcome-landing` | Follow-up: favicon.svg logo + OG/SEO meta for social share | agent |
| 2026-07-26 | `∞.welcome-landing` | Done: full-bleed welcome hero + i18n; types:check clean | agent |
| 2026-07-26 | `∞.employee-avatar` | Started: employee avatar upload & display | agent |
| 2026-07-26 | `∞.branding-plt-hrm` | Done: HRM + PLT Solutions brand; types:check clean | agent |
| 2026-07-26 | `∞.branding-plt-hrm` | Started: rebrand Laravel Starter Kit → HRM + PLT Solutions | agent |
| 2026-07-26 | `∞.dashboard-role-views` | Done: company vs self dashboard scopes; tests + types green | agent |
| 2026-07-26 | `∞.dashboard-role-views` | Started: company vs employee dashboard views | agent |
| 2026-07-26 | `∞.richer-dashboard` | Done: expanded summary API + Stitch charts/widgets; tests + types green | agent |
| 2026-07-26 | `∞.richer-dashboard` | Started: Stitch-aligned richer dashboard | agent |
| 2026-07-26 | `∞.sanctum-prod-session` | Done: merge APP_URL into Sanctum stateful; AuthApiTest green | agent |
| 2026-07-26 | `∞.sanctum-prod-session` | Started: fix prod login Session store not set | agent |
| 2026-07-19 | `∞.i18n-leave-types` | Leave types: localize by code on request form/list/balances; shared leave-labels helper | agent |
| 2026-07-19 | `∞.my-schedule-ux` | My Schedule: add missing leave label i18n keys (am/pm/hours/paid/on_leave/legend) | agent |
| 2026-07-19 | `∞.leave-schedule-attendance-payroll` | Done: leave overlay + attendance + payroll proration | agent |
| 2026-07-19 | `∞.leave-schedule-attendance-payroll` | Started: approved leave on calendar/attendance/payroll | agent |
| 2026-07-19 | `∞.payroll-run-edit-delete` | Done: PUT/DELETE payroll runs + show UI; tests green | agent |
| 2026-07-19 | `∞.payroll-run-edit-delete` | Started: update/delete payroll runs | agent |
| 2026-07-19 | `∞.payslips-ux` | Follow-up: detail Dialog (not Sheet) + fuller payslips i18n | agent |
| 2026-07-19 | `∞.payslips-ux` | Done: payslips list + detail sheet; types:check green | agent |
| 2026-07-19 | `∞.payslips-ux` | Started: payslips list + detail sheet UX | agent |
| 2026-07-19 | `∞.leave-create-dialogs` | Done: leave/types/holidays create dialogs; types:check green | agent |
| 2026-07-19 | `∞.leave-create-dialogs` | Started: leave pages create dialog UX | agent |
| 2026-07-19 | `∞.organization-ux` | Done: tree-first org UX + dialogs; types:check green | agent |
| 2026-07-19 | `∞.i18n-reports` | Reports type-switch: appliedReport + leave_type_code + locale dates; types:check + ReportApiTest green | agent |
| 2026-07-19 | `∞.report-format` | Done: reports duration + currency formatting; types:check green | agent |
| 2026-07-19 | `∞.report-format` | Started: format hours/minutes and money on Reports page | agent |
| 2026-07-19 | `∞.organization-ux` | Started: tree-first organization UX redesign | agent |
| 2026-07-19 | `∞.list-create-dialogs` | Done: payroll/performance/recruitment/documents create dialogs; types:check green | agent |
| 2026-07-19 | `∞.list-create-dialogs` | Started: create dialogs for four list pages | agent |
| 2026-07-19 | `∞.shifts-create-dialog` | Done: table-first shifts + create dialog; types:check green | agent |
| 2026-07-19 | `∞.shifts-create-dialog` | Started: table-first shifts page + create dialog | agent |
| 2026-07-19 | `∞.employees-create-dialog` | Done: table-first employees + create dialog; types:check green | agent |
| 2026-07-19 | `∞.employees-create-dialog` | Started: table-first employees page + create dialog | agent |
| 2026-07-19 | `∞.assets-create-dialog` | Done: table-first assets + create dialog; types:check green | agent |
| 2026-07-19 | `∞.assets-create-dialog` | Started: table-first assets page + create dialog | agent |
| 2026-07-19 | `∞.duration-format` | Done: readable hour/minute labels (vi/en); types:check green | agent |
| 2026-07-19 | `∞.duration-format` | Started: readable hour/minute labels (vi/en) | agent |
| 2026-07-19 | `∞.currency-format` | Done: VND/USD formatCurrency + CurrencyInput; payroll/offer wired; types:check green | agent |
| 2026-07-19 | `∞.currency-format` | Started: VND/USD money display + input mask | agent |
| 2026-07-19 | `∞.i18n-shifts` | Shifts: localize kind enums (standard/night/flexible/rotating) on list/create/show | agent |
| 2026-07-19 | `∞.i18n-notifications` | Notification inbox/bell + backend copy by type; lang/{vi,en}/notifications.php | agent |
| 2026-07-19 | `∞.i18n-reports` | Reports page: localize column headers + status cells; CSV header labels | agent |
| 2026-07-19 | `∞.roles-permission-tabs` | Done: /roles permissions grouped into domain tabs; types:check green | agent |
| 2026-07-19 | `∞.roles-permission-tabs` | Started: group /roles permissions into domain tabs | agent |
| 2026-07-19 | `∞.i18n-onboarding` | Onboarding index/show: localize task titles, templates, assignees; VI nhận việc wording | agent |
| 2026-07-18 | `∞.attendance-correction-badge` | Done: pending correction badge on attendance rows; types:check green | agent |
| 2026-07-18 | `∞.attendance-correction-badge` | Started: pending correction badge on attendance rows | agent |
| 2026-07-18 | `∞.attendance-record-employee` | Done: employee column on attendance records; types:check green | agent |
| 2026-07-18 | `∞.attendance-record-employee` | Started: show employee author on attendance records | agent |
| 2026-07-18 | `∞.attendance-datetime-tz` | Done: company TZ parse + toApiDateTime; AttendanceApiTest green | agent |
| 2026-07-18 | `∞.attendance-datetime-tz` | Started: fix naive datetime TZ for corrections | agent |
| 2026-07-18 | `∞.notification-header-panel` | Done: dropdown panel + see earlier link; types:check green | agent |
| 2026-07-18 | `∞.notification-header-panel` | Started: Facebook-style notification dropdown panel | agent |
| 2026-07-18 | `∞.notification-header-bell` | Done: header bell + red unread badge; types:check green | agent |
| 2026-07-18 | `∞.notification-header-bell` | Started: header notification bell with unread badge | agent |
| 2026-07-18 | `∞.attendance-ux` | Done: today-first attendance UX + corrections polish; types:check green | agent |
| 2026-07-18 | `∞.attendance-ux` | Started: today-first attendance UX + corrections polish | agent |
| 2026-07-18 | `∞.my-schedule-ux` | Done: table + month calendar views; types:check green | agent |
| 2026-07-18 | `∞.my-schedule-ux` | Started: table + month calendar views for My Schedule | agent |
| 2026-07-18 | `∞.employee-default-password` | Done: default env password + HR set/reset API/UI; tests green | agent |
| 2026-07-18 | `∞.employee-default-password` | Started: default env password + HR set/reset | agent |
| 2026-07-18 | `∞.employee-picker` | Wired roles assign via employee picker → linked user_id; types:check green | agent |
| 2026-07-18 | `∞.date-time-pickers` | Done: shared Date/Time/DateRange/DateTime pickers + migrate all forms; types:check green | agent |
| 2026-07-18 | `∞.employee-picker` | Shared employee picker dialog Done; wired 6 forms; types:check green | agent |
| 2026-07-18 | `∞.date-time-pickers` | Started; paused employee-picker; Wave 0 foundation next | agent |
| 2026-07-18 | `∞.employee-picker` | Paused (Todo) in favor of date-time-pickers | agent |
| 2026-07-18 | `∞.employee-picker` | Employee picker dialog started (shared component + form wiring) | agent |
| 2026-07-18 | `∞.i18n-audit-roles` | Audit/roles i18n polish Done; permissions parity + action labels; types:check green | agent |
| 2026-07-18 | `∞.i18n-audit-roles` | Future i18n polish started (audit-logs + roles) | agent |
| 2026-07-18 | `08.9` | Phase 08 Insight Done; 160 API tests + types:check; next = Future / v1.0.0 discussion | agent |
| 2026-07-18 | `08.0` | Phase 08 started; branch `feature/phase-08-insight`; steps 08.0–08.9 added | agent |
| 2026-07-18 | `07.9` | Phase 07 Recruitment & Ops Done; 138 API tests + types:check; next = Phase 08 Insight | agent |
| 2026-07-18 | `07.7`/`07.8` | Hire/Ops frontend (API modules, pages, nav, i18n) + seeders Done; types:check clean; seeders idempotent | agent |
| 2026-07-18 | `07.0` | Phase 07 started; branch `feature/phase-07-recruitment-ops`; steps 07.0–07.9 added | agent |
| 2026-07-18 | `01.13` | i18n hardening Done; DomainException lang + UI status/starter-kit; next = Phase 07 | agent |
| 2026-07-18 | `01.13` | i18n hardening started (status labels, starter-kit, DomainException lang) | agent |
| 2026-07-18 | `06.9` | Phase 06 Payroll Done; 111 API tests + types:check; next = Phase 07 Recruitment | agent |
| 2026-07-18 | `06.0` | Phase 06 started; branch `feature/phase-06-payroll`; steps 06.0–06.9 added | agent |
| 2026-07-18 | `04.9` | Phase 04 Leave Done; 104 API tests + types:check; next = Phase 06 Payroll | agent |
| 2026-07-18 | `04.0` | Phase 04 started; branch `feature/phase-04-leave`; steps 04.0–04.9 added | agent |
| 2026-07-18 | `03.9` | Phase 03 Attendance Done; 55 API tests + types:check; next = Phase 04 Leave | agent |
| 2026-07-18 | `03.0` | Phase 03 started; branch `feature/phase-03-attendance`; steps 03.0–03.9 added | agent |
| 2026-07-17 | `05.9` | Phase 05 Shift Done; 47 API tests + types:check; next = Phase 03 Attendance | agent |
| 2026-07-17 | `05.0` | Phase 05 started; branch `feature/phase-05-shift`; steps 05.0–05.9 added | agent |
| 2026-07-17 | `01.12` | i18n Done: docs + locale API + react-i18next + full current UI; next = Phase 05 | agent |
| 2026-07-17 | `01.12` | i18n foundation started (docs + locale persistence + full current UI) | agent |
| 2026-07-17 | `02.9` | Phase 02 Employee Done; 34 API tests + types:check; next = Phase 05 Shift | agent |
| 2026-07-17 | `02.0` | Phase 02 started; branch `feature/phase-02-employee`; steps 02.0–02.9 added | agent |
| 2026-07-17 | `01.11f` | Hardening exit: 28 API tests + types:check green; Snapshot → Phase 02 next | agent |
| 2026-07-17 | `01.11e` | PHASE_01 exit criteria ticked; SEEDING/ROUTING synced | agent |
| 2026-07-17 | `01.11d` | Role assign UI + audit on org/role mutations; AuditApiTest extended | agent |
| 2026-07-17 | `01.11c` | Org UI CRUD + teams/company edit; OrganizationApiTest 6 green | agent |
| 2026-07-17 | `01.11b` | Company settings AppLayout-only; `/settings` → company; stub copy removed | agent |
| 2026-07-17 | `01.11a` | 2FA challenge API + REST password UI Done; AuthApiTest 8 green | agent |
| 2026-07-17 | `01.11a` | Phase 01 hardening started; Snapshot → `01.11a` In progress | agent |
| 2026-07-17 | `01.7` hotfix | AuthProvider: drop `usePage` (breaks outside Inertia `withApp`); bootstrap via `/api/me` + `skipUnauthorized` | agent |
| 2026-07-17 | `01.10` | Phase 01 exit criteria met; Foundation marked Done (tag deferred) | agent |
| 2026-07-17 | `01.9` | Thin CRUD UIs for org/roles/settings/audit Done | agent |
| 2026-07-17 | `01.8` | Efficient Growth tokens + permission-gated admin nav stubs Done | agent |
| 2026-07-17 | `01.7` | API client + AuthProvider + REST login/logout Done | agent |
| 2026-07-17 | `01.6` | AuditLogger + audit-logs API Done; wired to settings/company updates | agent |
| 2026-07-17 | `01.5` | Settings storage + APIs Done; SettingsApiTest green | agent |
| 2026-07-17 | `01.4` | Organization hierarchy APIs + CompanySeeder Done; OrganizationApiTest green | agent |
| 2026-07-17 | `01.3` | Permissions/roles/policies + Roles API Done; RolesApiTest green | agent |
| 2026-07-17 | `01.2` | Auth API login/logout/me/forgot/reset Done; AuthApiTest green | agent |
| 2026-07-17 | `01.1` | Sanctum + API envelope + `/api/health` Done; 4 tests passed | agent |
| 2026-07-17 | `01.0` | Environment setup Done; branch `feature/phase-01-foundation`; DB + migrate + HTTP 200 verified | agent |
| 2026-07-17 | — | Phase 01 started; `01.0` set In progress | agent |
| 2026-07-17 | — | Translated PROGRESS.md to English-only content | agent |
| 2026-07-17 | — | Created PROGRESS.md; Phase 01 broken into steps 01.0–01.10 | bootstrap |

---

## Related

- [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)
- [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md)
- [../10-ai/AI_WORKFLOW.md](../10-ai/AI_WORKFLOW.md) — agents must update this file when finishing a step
