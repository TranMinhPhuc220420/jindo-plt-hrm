# AI Workflow

> Step-by-step workflow for AI agents implementing HRM work.
>
> Rules: [AI_RULES.md](./AI_RULES.md) · Context: [CONTEXT_LOADING.md](./CONTEXT_LOADING.md)

---

## Standard Feature Workflow

```
1. Clarify module + goal (from user prompt)
2. Load P0 + P1 docs for that module
3. Survey existing code (reuse first)
4. Plan minimal slice (API → service → policy → UI)
5. Implement backend contract
6. Implement frontend against API + UI rules
7. Add/adjust tests
8. Self-check review checklist
9. Summarize changes for the user
```

Do not skip step 2–3.

---

## Step Details

### 1. Orient

- Name the module (Leave, Payroll, …)
- Name the phase if relevant
- List out-of-scope items explicitly in the plan (mental or written)

### 2. Load context

Follow [CONTEXT_LOADING.md](./CONTEXT_LOADING.md).  
Use [FILE_PRIORITY.md](./FILE_PRIORITY.md) when trimming.

### 3. Reuse scan

Search for:

- Existing `*Service`
- Existing Form Requests / Policies
- Existing components (Button, Table, PermissionGate)
- Existing permissions in seeders

### 4. Design the slice

Typical vertical slice:

```
Migration (if needed)
  → Model/Repository
    → Service + events
      → Policy + Form Request
        → Controller + route
          → Pest tests
            → React page/hook + API client
```

Respect dependency direction at every layer.

### 5. Implement backend

Checklist:

- [ ] Thin controller
- [ ] Form Request validation
- [ ] Policy/permission
- [ ] Service owns rules + transaction
- [ ] Audit/event for important mutations
- [ ] Envelope responses
- [ ] Matches `docs/06-api`

### 6. Implement frontend

Checklist:

- [ ] Efficient Growth tokens
- [ ] PermissionGate
- [ ] 422 field mapping
- [ ] Loading/empty/error
- [ ] No business formulas as source of truth

### 7. Test

Minimum:

- Happy path
- 401/403 as relevant
- 422 critical fields
- One domain invariant (`LEAVE_BALANCE_INSUFFICIENT`, finalize immutability, …)

### 8. Self-review

Run [REVIEW_CHECKLIST.md](../08-development/REVIEW_CHECKLIST.md).  
Fix blockers before claiming done.

### 9. Report

Tell the user:

- What was implemented
- Key files
- Tests added
- Follow-ups / deferred items

---

## Docs-Only Workflow

```
1. Read PROJECT_LOGIC + section siblings
2. Write/update target doc only
3. Keep cross-links accurate
4. Do not refactor code
```

---

## Bugfix Workflow

```
1. Reproduce (test or steps)
2. Locate owning module
3. Load business + API docs
4. Fix at the correct layer (usually service/policy)
5. Add regression test
6. Avoid opportunistic refactors
```

---

## Explicitly Forbidden Shortcuts

1. Disabling authz to unblock UI
2. Writing SQL across foreign module tables “just this once”
3. Embedding payroll math in React
4. Huge unrelated reformatting PRs
5. Implementing Future Features mid Phase 01–08 without ask

---

## Git

Follow user rules:

- Commit only when asked
- PR only when asked
- Never force-push `main` / skip hooks unless user explicitly requests

See [GIT_WORKFLOW.md](../08-development/GIT_WORKFLOW.md).

---

## Related

- [PROMPT_GUIDELINES.md](./PROMPT_GUIDELINES.md)
- [../09-roadmap/MASTER_ROADMAP.md](../09-roadmap/MASTER_ROADMAP.md)
- [../08-development/CODING_STANDARD.md](../08-development/CODING_STANDARD.md)
