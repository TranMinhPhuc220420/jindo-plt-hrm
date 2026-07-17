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
| **Current phase** | Phase 01 — Foundation |
| **Current step** | `01.0` Environment setup |
| **Overall status** | Not started |
| **Last updated** | 2026-07-17 |
| **Last updated by** | — (bootstrap) |
| **Next action** | Set up DB and verify app boot; then `01.1` Sanctum + API envelope |
| **Blockers** | None |

---

## Phase Overview

| Phase | Name | Status | Milestone |
|-------|------|--------|-----------|
| 01 | Foundation | Todo | `v0.1.0` |
| 02 | Employee | Todo | `v0.2.0` |
| 03 | Attendance | Todo | `v0.3.0` (time domain) |
| 04 | Leave | Todo | `v0.3.0` |
| 05 | Shift | Todo | `v0.3.0` — **before / alongside full Attendance rules** |
| 06 | Payroll | Todo | `v0.4.0` |
| 07 | Recruitment & Ops | Todo | `v0.5.0` |
| 08 | Insight | Todo | `v0.6.0` |
| ∞ | Future | Backlog | — |

Recommended time-domain order: **05 → 03 → 04** (dependencies matter more than file numbers).

---

## Phase 01 — Foundation (step-by-step)

> Goal: Auth, authz, org, settings, audit writer, app shell.  
> Spec: [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md)

**Phase status:** `Todo`  
**Do not start Phase 02 until Exit Criteria below are all `[x]`.**

### 01.0 — Environment setup

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | `.env.example`, [DEPLOYMENT.md](../08-development/DEPLOYMENT.md) |

- [ ] MySQL database `jindo_plt_hrm` created and reachable
- [ ] `.env` matches local setup (DB, `APP_URL`, session)
- [ ] `composer install` / `npm install` OK
- [ ] `php artisan migrate` succeeds (existing migrations)
- [ ] `composer run dev` / app boot (Laravel + Vite) OK
- [ ] Working branch for Phase 01 (e.g. `feature/phase-01-foundation`)

**Notes:**

```
(empty)
```

---

### 01.1 — Sanctum + API platform spine

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [STACK_DECISION.md](../01-architecture/STACK_DECISION.md), [API_RESPONSE.md](../04-backend/API_RESPONSE.md), [ERROR_HANDLING.md](../04-backend/ERROR_HANDLING.md), [REST_STANDARD.md](../06-api/REST_STANDARD.md) |

- [ ] Install and configure Laravel Sanctum (SPA cookie)
- [ ] Register `routes/api.php` in bootstrap
- [ ] CORS / `SANCTUM_STATEFUL_DOMAINS` / session cookie for first-party SPA
- [ ] Shared API success/error envelope helpers
- [ ] Exception → JSON error mapping (422/401/403/404/…)
- [ ] Smoke: `/api/health` (or equivalent) returns the envelope

**Notes:**

```
(empty)
```

---

### 01.2 — Auth API + `/api/me`

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [AUTHENTICATION.md](../01-architecture/AUTHENTICATION.md), [AUTH_API.md](../06-api/AUTH_API.md) |

- [ ] Login (Sanctum SPA / Fortify behind AUTH_API)
- [ ] Logout
- [ ] CSRF / cookie flow documented in code comments or README if non-obvious
- [ ] `GET /api/me` returns user identity
- [ ] Forgot / reset password (per AUTH_API — happy path minimum)
- [ ] Feature tests: login, logout, unauthenticated 401

**Notes:**

```
(empty)
```

---

### 01.3 — Permissions, roles, policies pattern

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md), [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md), [ROLES_API.md](../06-api/ROLES_API.md), [SEEDING.md](../03-database/SEEDING.md) |

- [ ] Migrations: permissions, roles, pivots (users↔roles, roles↔permissions)
- [ ] `PermissionSeeder` — Foundation keys from the catalog
- [ ] `RoleSeeder` — Admin / HR / Manager / Employee bundles
- [ ] `/api/me` includes permission list
- [ ] Roles CRUD API (per ROLES_API)
- [ ] Sample policy / gate pattern (reuse for later modules)
- [ ] Tests: 403 when permission missing; never authorize by role name

**Minimum Foundation keys:**

- `can_view_organization`, `can_manage_organization`, `can_manage_company`
- `can_view_roles`, `can_manage_roles`, `can_assign_roles`
- `can_view_settings`, `can_manage_settings`
- `can_view_audit_logs`

**Notes:**

```
(empty)
```

---

### 01.4 — Organization hierarchy

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [02-business/organization](../02-business/organization/README.md), [ORGANIZATION_API.md](../06-api/ORGANIZATION_API.md), [ERD.md](../03-database/ERD.md) |

- [ ] Migrations: company, branch, department, team, position (+ indexes)
- [ ] Models + services (thin controllers)
- [ ] CRUD APIs under company scope
- [ ] Policies using Foundation permissions
- [ ] Seed: one demo company + org skeleton (non-prod)
- [ ] Tests: CRUD happy path + 403 + validation 422

**Notes:**

```
(empty)
```

---

### 01.5 — Settings

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [02-business/settings](../02-business/settings/README.md), [SETTINGS_API.md](../06-api/SETTINGS_API.md) |

- [ ] Settings storage (table / key-value per docs)
- [ ] Read/update APIs + permissions
- [ ] Seed defaults required by the app
- [ ] Tests: view/manage permission gates

**Notes:**

```
(empty)
```

---

### 01.6 — Audit log writer

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [02-business/audit](../02-business/audit/README.md), [AUDIT_API.md](../06-api/AUDIT_API.md) |

- [ ] `audit_logs` (or name per database naming docs) migration
- [ ] Audit writer service reusable by other modules
- [ ] Write audit for at least one sample mutation (e.g. org update / role assign)
- [ ] Minimal list API + `can_view_audit_logs` (full UI may be thin)
- [ ] Tests: writer creates a record; unauthorized list = 403

**Notes:**

```
(empty)
```

---

### 01.7 — Frontend API client + auth flow

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [API_CLIENT.md](../05-frontend/API_CLIENT.md), [AUTH_API.md](../06-api/AUTH_API.md), [STACK_DECISION.md](../01-architecture/STACK_DECISION.md) |

- [ ] Shared API client (CSRF, credentials, envelope parse, 401/422 handling)
- [ ] Login / logout UI calls REST (do not load domain data via Inertia props)
- [ ] Load `/api/me` into auth state
- [ ] Permission helpers / `PermissionGate` stub
- [ ] Do not expand Inertia demo pages for new HRM domains

**Notes:**

```
(empty)
```

---

### 01.8 — App shell + design tokens

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [LAYOUT.md](../05-frontend/LAYOUT.md), [ROUTING.md](../05-frontend/ROUTING.md), [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md), [07-uiux/stitch](../07-uiux/stitch/) |

- [ ] Efficient Growth / Stitch design tokens in Tailwind
- [ ] Auth layout + app sidebar shell (desktop)
- [ ] Gated route stubs: `/organization`, `/roles`, `/settings`, `/audit-logs`
- [ ] Nav show/hide by permissions
- [ ] Minimum responsive behavior (desktop + mobile web)

**Notes:**

```
(empty)
```

---

### 01.9 — Foundation UI pages (thin CRUD)

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | Org / Roles / Settings / Audit API + UI guidelines |

- [ ] Organization management UI (usable CRUD)
- [ ] Roles & permissions UI
- [ ] Settings UI
- [ ] Audit logs list UI (may be minimal)
- [ ] Empty / loading / error states per FE guidelines

**Notes:**

```
(empty)
```

---

### 01.10 — Phase 01 exit: tests, seed, smoke, tag

| Field | Value |
|-------|--------|
| Status | `Todo` |
| Started | — |
| Completed | — |
| Docs | [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md) Exit Criteria · [SEEDING.md](../03-database/SEEDING.md) |

- [ ] `PermissionSeeder` + `RoleSeeder` + org + admin user idempotent
- [ ] Feature tests cover authz deny paths
- [ ] Manual smoke: login → me → org CRUD → roles → settings → audit
- [ ] CI baseline (if present) green with Phase 01 tests
- [ ] Tag / note milestone `v0.1.0` (when the user requests a release)
- [ ] Update Phase Overview above → `Done`
- [ ] Update Phase 01 status in [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)

**Notes:**

```
(empty)
```

---

### Phase 01 — Exit Criteria (rollup)

- [ ] User login/logout; `/api/me` returns permissions
- [ ] Permissions DB-driven; Foundation catalog keys seeded
- [ ] Org hierarchy CRUD under company scope
- [ ] Settings + roles APIs usable; SPA routes gated
- [ ] App shell matches Stitch sidebar; Admin nav permission-aware
- [ ] Audit writer usable by later modules
- [ ] Tests cover authz deny paths

---

## Later phases (light checklist — expand when the phase starts)

> Mark Done only when that phase doc’s Exit Criteria are met. Add detailed steps to this file at phase kickoff (same granularity as Phase 01).

### Phase 02 — Employee

| Status | `Todo` | Spec | [PHASE_02_EMPLOYEE.md](./PHASE_02_EMPLOYEE.md) |

- [ ] Employee master + satellites (per phase doc)
- [ ] Employee permissions seeded & enforced
- [ ] API + UI + tests
- [ ] Detailed progress steps added to this file at kickoff

### Phase 05 — Shift _(recommended before full Attendance)_

| Status | `Todo` | Spec | [PHASE_05_SHIFT.md](./PHASE_05_SHIFT.md) |

- [ ] Kickoff: expand detailed steps here before coding

### Phase 03 — Attendance

| Status | `Todo` | Spec | [PHASE_03_ATTENDANCE.md](./PHASE_03_ATTENDANCE.md) |

- [ ] Kickoff: expand detailed steps here before coding

### Phase 04 — Leave

| Status | `Todo` | Spec | [PHASE_04_LEAVE.md](./PHASE_04_LEAVE.md) |

- [ ] Kickoff: expand detailed steps here before coding

### Phase 06 — Payroll

| Status | `Todo` | Spec | [PHASE_06_PAYROLL.md](./PHASE_06_PAYROLL.md) |

- [ ] Kickoff: expand detailed steps here before coding

### Phase 07 — Recruitment & Ops

| Status | `Todo` | Spec | [PHASE_07_RECRUITMENT.md](./PHASE_07_RECRUITMENT.md) |

- [ ] Kickoff: expand detailed steps here before coding

### Phase 08 — Insight

| Status | `Todo` | Spec | [PHASE_08_PERFORMANCE.md](./PHASE_08_PERFORMANCE.md) |

- [ ] Kickoff: expand detailed steps here before coding

---

## Activity log

> Append-only. Newest on top. One row per meaningful status change.

| Date | Step | Change | By |
|------|------|--------|-----|
| 2026-07-17 | — | Translated PROGRESS.md to English-only content | agent |
| 2026-07-17 | — | Created PROGRESS.md; Phase 01 broken into steps 01.0–01.10 | bootstrap |

---

## Related

- [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)
- [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md)
- [../10-ai/AI_WORKFLOW.md](../10-ai/AI_WORKFLOW.md) — agents must update this file when finishing a step
