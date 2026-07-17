# 00 — Overview

Project-level documentation: vision, scope, principles, glossary, and the master logic document.

Everyone (human or AI) should start here before changing business rules or architecture.

---

## Documents

| File | Purpose |
|------|---------|
| [PROJECT_LOGIC.md](./PROJECT_LOGIC.md) | **Single source of truth** — business domain, modules, relationships, layers, phases |
| [PROJECT_VISION.md](./PROJECT_VISION.md) | Product vision, goals, platforms, success criteria, long-term direction |
| [BUSINESS_SCOPE.md](./BUSINESS_SCOPE.md) | In-scope / out-of-scope boundaries, lifecycle, phase mapping |
| [DEVELOPMENT_PRINCIPLES.md](./DEVELOPMENT_PRINCIPLES.md) | Modular, permission-first, auditability, layering, AI rules |
| [GLOSSARY.md](./GLOSSARY.md) | Shared domain and technical terminology |

---

## Reading Order

1. **PROJECT_VISION.md** — why we build this
2. **BUSINESS_SCOPE.md** — what is included now vs later
3. **DEVELOPMENT_PRINCIPLES.md** — how we build
4. **PROJECT_LOGIC.md** — full business and architecture logic
5. **GLOSSARY.md** — terms used across the docs

---

## Rules

- If another document conflicts with `PROJECT_LOGIC.md`, update the other document or explicitly revise `PROJECT_LOGIC.md` first.
- Do not invent new domains or reverse dependency direction without updating overview docs.
- Use glossary terms consistently in APIs, database naming, and UI.

---

## Next Sections

| Path | Topic |
|------|--------|
| `docs/01-architecture/` | System, backend, frontend, database, API, auth |
| `docs/02-business/` | Per-module business documentation |
| `docs/03-database/` | Naming, ERD, migrations, seeding |
| `docs/04-backend/` | Laravel structure, services, events, testing |
| `docs/05-frontend/` | React structure, UI guidelines, state |
| `docs/06-api/` | REST standards and module APIs |
| `docs/07-uiux/` | Design system and accessibility |
| `docs/08-development/` | Git, review, release, deployment |
| `docs/09-roadmap/` | Phased delivery plan |
| `docs/10-ai/` | AI agent rules and workflow |
