# Review Checklist

> PR review gate for human and AI reviewers.
>
> Principles: [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)

---

## Architecture & Modules

- [ ] Dependency arrow points downward (no Attendance → Payroll writes)
- [ ] Cross-module access via services, not foreign repositories
- [ ] Controllers stay thin; rules in services
- [ ] No circular events to finish one use case
- [ ] Multi-company / `company_id` not painted into a corner

---

## Security & Authz

- [ ] Endpoints authenticated as required
- [ ] Policies/permissions used — **not** role-name hardcoding
- [ ] Sensitive fields (salary, bank, tax) gated
- [ ] No secrets committed
- [ ] Uploads validated (type/size); downloads authorized

---

## Data & Migrations

- [ ] Naming follows [DATABASE_NAMING.md](../03-database/DATABASE_NAMING.md)
- [ ] Migrations additive-first; destructive changes justified
- [ ] Indexes for new FKs / common filters
- [ ] Seeders idempotent; demo data guarded from production

---

## API

- [ ] REST paths/methods match `docs/06-api/`
- [ ] Success/error envelopes consistent
- [ ] 422 field errors for validation
- [ ] Domain `error_code` where clients must branch
- [ ] Breaking response changes avoided or versioned

---

## Frontend / UI

- [ ] Follows Efficient Growth tokens ([UI_RULES.md](../05-frontend/UI_RULES.md))
- [ ] PermissionGate for actions/menus
- [ ] Loading / empty / error states present
- [ ] Forms map 422 errors to fields
- [ ] No business formulas as source of truth in UI

---

## Audit & Side Effects

- [ ] Employee edit / salary change / attendance approve / leave reject / asset assign audited when touched
- [ ] Notifications via events/queues, not SMTP in controllers
- [ ] Jobs idempotent where retried

---

## Tests & Quality

- [ ] Happy path covered
- [ ] 401/403/422 cases for critical endpoints
- [ ] Company-scope isolation considered
- [ ] CI green (format, lint, types, tests)

### Definition of Done (PR)

Enforce the minimums in [TESTING.md](../04-backend/TESTING.md). Reject or request changes when missing:

- [ ] **API / feature change** — happy path + 401 + 403 + 422 (critical fields) + company-scope isolation where the resource is company-owned
- [ ] **Policy / permission change** — update or add a Pest policy matrix under `tests/Unit/Policies/` (own / peer / manager / other company / missing permission)
- [ ] **Job / listener / notification change** — test `handle()` (or sync queue) with `Mail::fake` / `Storage::fake` / `Notification::fake` / `Queue::fake` as appropriate; do not only assert “job was pushed”
- [ ] **Critical UI path** — Playwright smoke under `e2e/` covers the flow (login, employees, leave, attendance, payroll, logout) or an existing smoke is updated
- [ ] **Production bug fix** — include a regression test that would have failed before the fix
- [ ] **Quarterly triage** — each production incident maps to a missing-test ticket; close only when the regression test lands

Helpers: `actingUser`, `actingUserInCompany`, `foreignCompanyUser`, `assertCannotAccessOtherCompany` in `tests/Pest.php`.

### Test impact rule

Changing `app/Policies/*` or calculation services (`Leave*`, `Payroll*`, `AttendanceMetrics*`, employee status transitions) without adding/updating tests is a review blocker unless explicitly waived.

---

## Docs

- [ ] Contract/schema changes reflected in docs
- [ ] Glossary terms used consistently
- [ ] Roadmap phase notes updated if scope shifts

---

## Merge Decision

Approve only if blockers above are resolved or explicitly waived with reason in the PR.
