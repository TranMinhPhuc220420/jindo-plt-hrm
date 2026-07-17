# 10 — AI

Rules and workflows for AI agents working on this repo.

**Start here:** [AI_RULES.md](./AI_RULES.md) → [CONTEXT_LOADING.md](./CONTEXT_LOADING.md)

---

## Documents

| File | Purpose |
|------|---------|
| [AI_RULES.md](./AI_RULES.md) | Hard constraints |
| [PROMPT_GUIDELINES.md](./PROMPT_GUIDELINES.md) | How to write prompts |
| [CONTEXT_LOADING.md](./CONTEXT_LOADING.md) | What to load first |
| [FILE_PRIORITY.md](./FILE_PRIORITY.md) | Trust order for docs/code |
| [AI_WORKFLOW.md](./AI_WORKFLOW.md) | End-to-end agent workflow |

---

## Non-negotiables (summary)

- Follow [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)
- Respect [STACK_DECISION.md](../01-architecture/STACK_DECISION.md) (REST + Sanctum SPA)
- Permission-first; business logic in services
- Do not invent domains or reverse dependencies

---

## Related

- [../README.md](../README.md)
- [../00-overview/DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)
