# File Priority

> Which files matter most when an AI agent must choose what to trust or load first.

---

## Priority Tiers

### P0 — Non-negotiable source of truth

| File | Why |
|------|-----|
| `docs/00-overview/PROJECT_LOGIC.md` | Master business + architecture intent |
| `docs/00-overview/DEVELOPMENT_PRINCIPLES.md` | Build constraints + AI rules summary |
| `docs/01-architecture/STACK_DECISION.md` | REST + Sanctum SPA (not Inertia for new HRM) |
| `docs/01-architecture/DEPENDENCY_RULES.md` | Illegal coupling prevention |
| `docs/10-ai/AI_RULES.md` | Agent hard rules |

### P1 — Task contracts

| Area | Files |
|------|--------|
| Domain rules | `docs/02-business/{module}/README.md` |
| HTTP contract | `docs/06-api/REST_STANDARD.md`, `docs/06-api/{MODULE}_API.md` |
| Data rules | `docs/03-database/DATABASE_NAMING.md`, `DATABASE_CONVENTIONS.md`, `MIGRATION_RULES.md` |
| Backend patterns | `docs/04-backend/SERVICES.md`, `VALIDATION.md`, `POLICIES.md`, `API_RESPONSE.md` |
| Frontend patterns | `docs/05-frontend/UI_RULES.md`, `I18N.md`, `API_CLIENT.md`, `REACT_STRUCTURE.md` |
| Design | `docs/07-uiux/DESIGN_SYSTEM.md`, `COLOR_SYSTEM.md` |

### P2 — Supporting architecture

| Files |
|-------|
| `docs/01-architecture/BACKEND_ARCHITECTURE.md` |
| `docs/01-architecture/FRONTEND_ARCHITECTURE.md` |
| `docs/01-architecture/AUTHENTICATION.md` |
| `docs/01-architecture/AUTHORIZATION.md` |
| `docs/01-architecture/PERMISSIONS_CATALOG.md` |
| `docs/01-architecture/EVENT_FLOW.md` |
| `docs/01-architecture/DATABASE_ARCHITECTURE.md` |
| `docs/03-database/ERD.md`, `TABLE_RELATIONSHIP.md`, `INDEXING.md` |

### P3 — Delivery & process

| Files |
|-------|
| `docs/09-roadmap/MASTER_ROADMAP.md` + relevant `PHASE_*.md` |
| `docs/08-development/REVIEW_CHECKLIST.md` |
| `docs/08-development/CODING_STANDARD.md` |
| `docs/04-backend/TESTING.md` |

### P4 — Reference exports

| Files |
|-------|
| `docs/07-uiux/stitch/**` (DESIGN.md, HTML, PNG) |
| Glossary, vision, scope when naming/product questions arise |

---

## Code vs Docs

| If conflict | Prefer |
|-------------|--------|
| Business rule unclear | Docs (P0/P1 business) then update code |
| API already shipped differently | Discuss/fix docs to match reality **or** fix code to docs — do not fork silently |
| One-off legacy in starter kit (Inertia demos) | HRM target docs (`REST` + module layout) for new HRM work |

Starter-kit files under `resources/js/pages/auth`, Fortify, etc. are scaffolding — do not treat them as HRM domain truth over `docs/02-business` / `docs/06-api`.

---

## Module File Hotspots (code)

When implementing a module, prioritize existing:

```
app/Services/{Module}/**
app/Http/Controllers/**/{Module}/**
app/Http/Requests/{Module}/**
app/Policies/*{Module}*
app/Models/** (owned tables)
routes/api.php (or module route files)
tests/Feature/{Module}/**
resources/js/pages/{module}/**
resources/js/features/{module}/**
```

---

## Do Not Prioritize

- Random blog patterns that contradict docs
- Copying another product’s schema
- Future Features as current sprint requirements
- `vendor/` / `node_modules/` as design references

---

## Related

- [CONTEXT_LOADING.md](./CONTEXT_LOADING.md)
- [AI_RULES.md](./AI_RULES.md)
