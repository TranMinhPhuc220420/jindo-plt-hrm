# Git Workflow

> How the team commits, reviews, and integrates changes.
>
> Branches: [BRANCH_STRATEGY.md](./BRANCH_STRATEGY.md) · Release: [RELEASE_PROCESS.md](./RELEASE_PROCESS.md)

---

## Principles

1. `main` stays releasable.
2. Work happens on short-lived branches.
3. Every change lands via Pull Request (PR) with review.
4. CI must pass before merge.
5. Do not commit secrets (`.env`, keys, dumps with PII).

---

## Daily Flow

```
1. Update main
2. Create branch from main
3. Implement + test locally
4. Commit with clear messages
5. Push + open PR
6. Address review + CI
7. Merge (squash or merge commit per team default)
8. Delete branch
```

---

## Commit Messages

Prefer concise, imperative subject focused on **why**:

```
Add leave approval policy checks

Ensure managers can only approve direct reports and emit audit events.
```

| Good | Avoid |
|------|--------|
| `Fix leave balance race on concurrent approve` | `fix stuff` |
| `Add attendance summary endpoint for payroll` | `WIP` |
| `Document Efficient Growth color tokens` | `Update files` |

Do not commit generated noise unless it is part of the agreed build (e.g. intentional Wayfinder regen).

---

## What Goes in a PR

- Related code + tests + docs updates when contracts change
- Migration + seeder/permission updates when schema/authz changes
- Screenshots for non-trivial UI (optional but helpful)

Keep PRs focused on one feature/fix when possible.

---

## Hooks & Quality Gates

Typical local/CI checks (project tooling):

- Pint / formatter
- ESLint / Prettier (frontend)
- PHPStan / Larastan (as configured)
- Pest tests
- `tsc --noEmit` / types check

Do not use `--no-verify` unless explicitly approved for an emergency.

---

## Secrets & Data

Never commit:

- `.env`, API keys, service account JSON
- Production DB dumps
- Real employee PII in fixtures

Use `.env.example` with placeholder values only.

---

## Hotfix

1. Branch from the release tag or `main` per [RELEASE_PROCESS.md](./RELEASE_PROCESS.md)
2. Minimal fix + test
3. Fast-track review
4. Deploy + back-merge to `main` if needed

---

## Related Documents

- [BRANCH_STRATEGY.md](./BRANCH_STRATEGY.md)
- [REVIEW_CHECKLIST.md](./REVIEW_CHECKLIST.md)
- [CODING_STANDARD.md](./CODING_STANDARD.md)
