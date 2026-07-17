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

---

## Docs

- [ ] Contract/schema changes reflected in docs
- [ ] Glossary terms used consistently
- [ ] Roadmap phase notes updated if scope shifts

---

## Merge Decision

Approve only if blockers above are resolved or explicitly waived with reason in the PR.
