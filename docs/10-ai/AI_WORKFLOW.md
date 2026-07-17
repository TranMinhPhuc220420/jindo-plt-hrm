# AI Workflow

> Step-by-step workflow for AI agents implementing HRM work.
>
> Rules: [AI_RULES.md](./AI_RULES.md) · Context: [CONTEXT_LOADING.md](./CONTEXT_LOADING.md)

---

## Standard Feature Workflow

```
1. Read PROGRESS.md — confirm current step / do not skip ahead
2. Clarify module + goal (from user prompt)
3. Load P0 + P1 docs for that module
4. Survey existing code (reuse first)
5. Plan minimal slice (API → service → policy → UI)
6. Implement backend contract
7. Implement frontend against API + UI rules
8. Add/adjust tests
9. Self-check review checklist
10. Update PROGRESS.md (status, checkboxes, snapshot, activity log)
11. Summarize changes for the user
```

Do not skip context loading or the PROGRESS.md update.

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

### 10. Update progress

Edit [PROGRESS.md](../09-roadmap/PROGRESS.md):

1. Set the finished step to `Done` (or `Blocked` + reason).
2. Tick completed checkboxes; fill Notes if useful.
3. Refresh **Snapshot** (`Current step`, `Next action`, `Last updated`).
4. Append a row to **Activity log**.
5. If the user is about to start the next step in the same session, set that step to `In progress`.

### 11. Report

Tell the user:

- What was implemented
- Key files
- Tests added
- PROGRESS.md updates (step id + new next action)
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
- [../09-roadmap/PROGRESS.md](../09-roadmap/PROGRESS.md)
- [../09-roadmap/MASTER_ROADMAP.md](../09-roadmap/MASTER_ROADMAP.md)
- [../08-development/CODING_STANDARD.md](../08-development/CODING_STANDARD.md)
