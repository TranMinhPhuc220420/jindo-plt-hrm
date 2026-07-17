# Branch Strategy

> Naming and lifetime rules for Git branches.
>
> Workflow: [GIT_WORKFLOW.md](./GIT_WORKFLOW.md)

---

## Long-Lived Branches

| Branch | Purpose |
|--------|---------|
| `main` | Default integration branch; always green / releasable |
| `develop` (optional) | Only if the team explicitly uses a two-branch model |

Prefer **trunk-based** style: feature branches → `main`. Avoid long-lived diverging environment branches.

---

## Short-Lived Branches

| Pattern | Use |
|---------|-----|
| `feature/<ticket>-<slug>` | New capability |
| `fix/<ticket>-<slug>` | Bug fix |
| `chore/<slug>` | Tooling, deps, non-product chores |
| `docs/<slug>` | Documentation only |
| `hotfix/<slug>` | Production emergency fix |

Examples:

```
feature/hrm-12-leave-approval
fix/hrm-40-attendance-double-checkin
docs/api-payroll-finalize
hotfix/payslip-pdf-500
```

---

## Rules

1. Branch from up-to-date `main`.
2. Keep lifetime short (days, not weeks). Rebase/merge `main` regularly.
3. One primary concern per branch.
4. Delete remote branch after merge.
5. Do not force-push `main`.
6. Force-push feature branches only if alone on that branch and team allows.

---

## Mapping to Roadmap

Optional naming when useful:

```
feature/phase-03-attendance-check-in
feature/phase-06-payroll-run
```

Roadmap phase docs live in `docs/09-roadmap/`; branch names need not encode every phase.

---

## Environments

| Environment | Deploys from |
|-------------|--------------|
| Local | Any branch |
| Staging | `main` or release candidate tag |
| Production | Release tag / approved `main` commit |

See [DEPLOYMENT.md](./DEPLOYMENT.md).

---

## Related Documents

- [GIT_WORKFLOW.md](./GIT_WORKFLOW.md)
- [RELEASE_PROCESS.md](./RELEASE_PROCESS.md)
