# HRM Documentation

Documentation for the Jindo PLT HRM platform.

**Single source of truth for business & architecture intent:** [00-overview/PROJECT_LOGIC.md](./00-overview/PROJECT_LOGIC.md)

**Stack (binding):** [01-architecture/STACK_DECISION.md](./01-architecture/STACK_DECISION.md) — Laravel REST `/api` + React SPA + Sanctum cookie auth

**AI agents start here:** [10-ai/AI_RULES.md](./10-ai/AI_RULES.md) → [10-ai/CONTEXT_LOADING.md](./10-ai/CONTEXT_LOADING.md)

**Progress (what’s done / next):** [09-roadmap/PROGRESS.md](./09-roadmap/PROGRESS.md)

---

## Index

| Section | Path | Contents |
|---------|------|----------|
| Overview | [00-overview/](./00-overview/) | Vision, scope, principles, glossary, project logic |
| Architecture | [01-architecture/](./01-architecture/) | Stack, system, auth, permissions catalog, deps |
| Business | [02-business/](./02-business/) | Per-module rules (incl. org / settings / audit) |
| Database | [03-database/](./03-database/) | Naming, ERD, indexes, migrations, seeding |
| Backend | [04-backend/](./04-backend/) | Laravel layers, services, policies, testing |
| Frontend | [05-frontend/](./05-frontend/) | React structure, UI patterns, API client |
| API | [06-api/](./06-api/) | REST standard + module endpoint contracts |
| UI/UX | [07-uiux/](./07-uiux/) | Efficient Growth design system (Stitch) |
| Development | [08-development/](./08-development/) | Git, review, release, deployment |
| Roadmap | [09-roadmap/](./09-roadmap/) | Phased delivery plan |
| AI | [10-ai/](./10-ai/) | Agent rules, prompts, context, workflow |

Each section has a `README.md` index.

---

## Reading Order (humans)

1. [00-overview/README.md](./00-overview/README.md)
2. [01-architecture/STACK_DECISION.md](./01-architecture/STACK_DECISION.md) → [SYSTEM_ARCHITECTURE.md](./01-architecture/SYSTEM_ARCHITECTURE.md)
3. Module under [02-business/](./02-business/) you will work on
4. Matching [06-api/](./06-api/) + [07-uiux/DESIGN_SYSTEM.md](./07-uiux/DESIGN_SYSTEM.md)
5. [09-roadmap/MASTER_ROADMAP.md](./09-roadmap/MASTER_ROADMAP.md)

---

## Design Source

Stitch export (Apple Style HRM Dashboard / Efficient Growth): [07-uiux/stitch/](./07-uiux/stitch/)
