# AI Rules

> Mandatory rules for every AI agent working on this HRM codebase.
>
> Source: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) §11  
> Principles: [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)

---

## Absolute Rules

1. **Never violate business logic** documented in `docs/00-overview/` and `docs/02-business/`.
2. **Never duplicate existing functionality** — search services/components first.
3. **Always reuse Services before creating new ones.**
4. **Controllers must stay thin.**
5. **Business logic belongs in Services.**
6. **Validation belongs in Form Requests.**
7. **React components should remain reusable** and free of server business truth.
8. **Use TypeScript** for frontend domain code.
9. **Follow RESTful API conventions** in `docs/06-api/`.
10. **Every feature must include authorization** (permission-first, not role-name checks).
11. **Every important action should be auditable** (employee edit, salary change, attendance approve, leave reject, asset assign, …).
12. **Every module should be independently testable.**
13. **Prefer composition over inheritance.**
14. **Keep modules loosely coupled** — dependencies flow downward only.
15. **Design for future SaaS compatibility** (`company_id` scope, no singleton-company assumptions).

---

## Hard Nots

| Do not | Do instead |
|--------|------------|
| Expand Inertia pages for new HRM domains | REST `/api` + React SPA ([STACK_DECISION.md](../01-architecture/STACK_DECISION.md)) |
| `if (role === 'HR')` / `role === 'admin'` | Permission keys + [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md) |
| Attendance writing payslips | Payroll consumes AttendanceService summaries |
| Business rules in controllers or React | Services + Form Requests |
| Invent API shapes that contradict `docs/06-api/` | Update docs first if contract must change |
| Invent schema that contradicts `docs/03-database/` | Follow naming/conventions/migrations |
| Purple/indigo “AI default” UI theme | Efficient Growth / Stitch tokens (`docs/07-uiux/`) |
| Commit secrets or real PII | `.env.example` placeholders only |
| `--no-verify` / skip tests casually | Fix CI; add proportional tests |
| Circular module/event dependencies | Follow [DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md) |

---

## Conflict Resolution

1. `PROJECT_LOGIC.md` wins for business/architecture intent.
2. Module business README wins for that domain’s rules.
3. API docs win for HTTP contracts.
4. If code and docs diverge, **fix docs intentionally** or **change code to match docs** — do not silently invent a third behavior.

---

## Security & Privacy

- Never log passwords, 2FA secrets, or full bank payloads.
- Never weaken authz “to make the demo work”.
- Treat employee PII as sensitive in seeds, tests, and examples (`@example.test`).

---

## UI

- Follow [UI_RULES.md](../05-frontend/UI_RULES.md) and [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md).
- Permission-gate menus and actions; API remains the real enforcement.

---

## Before Finishing a Task

Run the mental checklist in [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md) and [REVIEW_CHECKLIST.md](../08-development/REVIEW_CHECKLIST.md).

**Always update [PROGRESS.md](../09-roadmap/PROGRESS.md):** mark the step `Done` (or `Blocked`), tick checkboxes, refresh Snapshot + Activity log. Do not claim a step complete without updating that file.

---

## Related

- [PROMPT_GUIDELINES.md](./PROMPT_GUIDELINES.md)
- [CONTEXT_LOADING.md](./CONTEXT_LOADING.md)
- [../09-roadmap/PROGRESS.md](../09-roadmap/PROGRESS.md)
- [FILE_PRIORITY.md](./FILE_PRIORITY.md)
- [AI_WORKFLOW.md](./AI_WORKFLOW.md)
