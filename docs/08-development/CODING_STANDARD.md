# Coding Standard

> Language and project conventions for backend and frontend.
>
> Principles: [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)  
> Backend: `docs/04-backend/` · Frontend: `docs/05-frontend/` · UI: `docs/07-uiux/`

---

## Universal Rules

1. Prefer clarity over cleverness.
2. Do not duplicate existing functionality — reuse services/components first.
3. Respect module dependency direction ([DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md)).
4. Permission-first — never `if ($user->role === 'HR')` / `role === 'admin'` for authz.
5. Important mutations must be auditable.
6. Multi-company ready: prefer `company_id` scoping in data access.
7. No secrets in code.

---

## PHP / Laravel

| Topic | Standard |
|-------|----------|
| PHP | ^8.3 style, typed properties/params/returns where practical |
| Controllers | Thin — HTTP in/out only |
| Business rules | Services (or focused Actions) |
| Validation | Form Requests |
| Authz | Policies + permission keys |
| Persistence | Repositories / Eloquent models owned by module |
| Formatting | Laravel Pint |
| Static analysis | Larastan/PHPStan as configured |
| Tests | Pest |

Follow:

- [SERVICES.md](../04-backend/SERVICES.md)
- [VALIDATION.md](../04-backend/VALIDATION.md)
- [POLICIES.md](../04-backend/POLICIES.md)
- [MIGRATION_RULES.md](../03-database/MIGRATION_RULES.md)
- [DATABASE_NAMING.md](../03-database/DATABASE_NAMING.md)

---

## TypeScript / React

| Topic | Standard |
|-------|----------|
| Language | TypeScript strict enough for domain safety; avoid `any` |
| UI | React function components |
| Styling | Tailwind + Efficient Growth tokens |
| API | Central client + envelope types |
| Formatting | Prettier |
| Lint | ESLint |
| Types check | `tsc --noEmit` |

Follow:

- [REACT_STRUCTURE.md](../05-frontend/REACT_STRUCTURE.md)
- [UI_RULES.md](../05-frontend/UI_RULES.md)
- [API_CLIENT.md](../05-frontend/API_CLIENT.md)
- [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)

Do not put payroll/leave business truth in the UI.

---

## Naming

| Area | Convention |
|------|------------|
| PHP classes | PascalCase |
| PHP methods | camelCase |
| DB tables/columns | snake_case plural tables |
| TS components | PascalCase |
| TS functions | camelCase |
| Permissions | `can_*` snake strings |
| API JSON | snake_case |
| Routes | plural kebab resources |

Use [GLOSSARY.md](../00-overview/GLOSSARY.md) terms consistently.

---

## Comments

- Prefer self-explanatory code
- Comment **why** for non-obvious domain rules
- Do not leave commented-out dead code in PRs

---

## Testing Expectation

New behavior should include tests proportional to risk:

- Policies for sensitive actions
- Service rules for money/time/status transitions
- Feature/API tests for happy path + 403/422

See [TESTING.md](../04-backend/TESTING.md).

---

## AI-Assisted Coding

Agents must follow [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md) §10 and `docs/10-ai/` when filled.  
Never invent APIs or schema that contradict `docs/06-api/` and `docs/03-database/`.

---

## Related Documents

- [REVIEW_CHECKLIST.md](./REVIEW_CHECKLIST.md)
- [../04-backend/LARAVEL_STRUCTURE.md](../04-backend/LARAVEL_STRUCTURE.md)
- [../05-frontend/COMPONENT_GUIDELINE.md](../05-frontend/COMPONENT_GUIDELINE.md)
