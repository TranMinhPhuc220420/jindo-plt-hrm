# Context Loading

> What an AI agent should read before editing code or docs.
>
> Priority order: [FILE_PRIORITY.md](./FILE_PRIORITY.md)

---

## Golden Rule

**Read docs before inventing structure.**  
This repo’s `docs/` is the contract. Code implements the docs.

---

## Minimum Context (every non-trivial task)

Always load:

1. [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) — or the relevant section if already known
2. [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)
3. [STACK_DECISION.md](../01-architecture/STACK_DECISION.md) — REST + Sanctum SPA
4. [DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md)
5. [AI_RULES.md](./AI_RULES.md)

---

## By Task Type

### Backend feature (API + service)

| Order | Doc |
|-------|-----|
| 1 | `docs/02-business/{module}/README.md` |
| 2 | `docs/06-api/{MODULE}_API.md` |
| 3 | `docs/04-backend/SERVICES.md` + `VALIDATION.md` + `POLICIES.md` |
| 4 | `docs/03-database/` naming/conventions (and ERD if schema) |
| 5 | Existing module code (services, routes, tests) |

### Frontend feature

| Order | Doc |
|-------|-----|
| 1 | `docs/05-frontend/UI_RULES.md` + `LAYOUT.md` |
| 2 | `docs/07-uiux/DESIGN_SYSTEM.md` (+ color/type as needed) |
| 3 | Matching `docs/06-api/` + `docs/05-frontend/API_CLIENT.md` |
| 4 | `docs/02-business/{module}/` for labels/flows |
| 5 | Existing pages/components in that module |

### Database / migration

| Order | Doc |
|-------|-----|
| 1 | `DATABASE_NAMING.md` + `DATABASE_CONVENTIONS.md` |
| 2 | `MIGRATION_RULES.md` + `TABLE_RELATIONSHIP.md` |
| 3 | Module business + API needs |
| 4 | Existing migrations for patterns |

### Auth / permissions

| Order | Doc |
|-------|-----|
| 1 | `AUTHENTICATION.md` + `AUTHORIZATION.md` |
| 2 | `AUTH_API.md` + `POLICIES.md` |
| 3 | `SEEDING.md` (permission keys) |

### Bug fix

| Order | Action |
|-------|--------|
| 1 | Reproduce from failing test/log |
| 2 | Load owning module business + API doc |
| 3 | Trace service/policy — not random rewrites |

### Docs-only task

| Order | Doc |
|-------|-----|
| 1 | `PROJECT_LOGIC.md` |
| 2 | Sibling docs in that section |
| 3 | Do not “fix” code unless asked |

---

## Phase-Aware Loading

If the user names a phase, also open:

- `docs/09-roadmap/PHASE_XX_*.md`
- Linked API/business docs in that phase’s “Key Docs”

Do not implement Future Features during Phase 01–08 unless explicitly requested.

---

## Codebase Exploration Tips

1. Prefer module folders matching `employee`, `leave`, `payroll`, …
2. Reuse before create: Services → Actions → new classes
3. Check tests for intended behavior examples
4. Stitch reference UI: `docs/07-uiux/stitch/` when touching shell/dashboard

---

## Context Budget

When limited:

1. Keep dependency rules + module business + API contract
2. Drop lengthy future/roadmap prose
3. Never drop authz/audit requirements for money/time mutations

---

## Related

- [FILE_PRIORITY.md](./FILE_PRIORITY.md)
- [AI_WORKFLOW.md](./AI_WORKFLOW.md)
- [../00-overview/README.md](../00-overview/README.md)
