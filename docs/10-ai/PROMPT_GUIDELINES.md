# Prompt Guidelines

> How humans should prompt AI agents, and how agents should interpret tasks on this repo.

---

## For Humans (recommended prompt shape)

Include:

1. **Goal** — what to build/fix/document
2. **Module** — e.g. Leave, Payroll, Employee
3. **Phase** — e.g. Phase 04 (optional but helpful)
4. **Constraints** — “no new deps”, “API only”, “docs only”
5. **Acceptance** — tests, endpoints, UI screen

Example:

```
Implement leave request approve/reject for Phase 04.

Follow docs/02-business/leave and docs/06-api/LEAVE_API.md.
Use permission can_approve_leave + policy scope.
Emit audit on reject; notify via event.
Add Pest feature tests for 200/403/LEAVE_BALANCE_INSUFFICIENT.
Do not touch payroll tables.
```

---

## For Agents (interpretation rules)

When the user is vague:

1. Infer module from paths/keywords.
2. Load docs per [CONTEXT_LOADING.md](./CONTEXT_LOADING.md).
3. Prefer the smallest change that satisfies the request.
4. Ask only when blocked by a true product choice (e.g. unpaid leave affects payroll net — confirm if unspecified and high impact).

Do **not** ask permission for routine implementation details already settled in docs.

---

## Good Task Verbs

| Verb | Expectation |
|------|-------------|
| Implement | Code + tests + match API/docs |
| Document | Docs only; no drive-by refactors |
| Fix | Minimal bugfix + regression test |
| Refactor | Behavior-preserving; state that explicitly |
| Scaffold | Structure only if asked; otherwise complete the slice |

---

## Scope Control Phrases

Humans can say:

- `docs only`
- `backend only` / `frontend only`
- `no migration`
- `follow Phase 0X exit criteria`
- `do not open a PR` / `commit` (agents follow user git rules)

Agents must honor these literally.

---

## Anti-Prompts (avoid)

- “Rebuild the whole HRM”
- “Just make it work” without module/docs anchor
- “Use admin role bypass”
- “Copy from another SaaS and adapt” without aligning to PROJECT_LOGIC

---

## Output Expectations

Agents should:

- Summarize what changed and where
- Call out doc deviations if any
- Not dump huge unrelated refactors
- Not invent roadmap scope from Future Features unless asked

---

## Related

- [AI_RULES.md](./AI_RULES.md)
- [AI_WORKFLOW.md](./AI_WORKFLOW.md)
- [../09-roadmap/MASTER_ROADMAP.md](../09-roadmap/MASTER_ROADMAP.md)
