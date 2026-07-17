# Release Process

> How versions move from `main` to staging and production.
>
> Deploy: [DEPLOYMENT.md](./DEPLOYMENT.md) · Git: [GIT_WORKFLOW.md](./GIT_WORKFLOW.md)

---

## Goals

1. Predictable, repeatable releases
2. Migrations applied safely
3. Fast rollback path
4. Changelog clarity for the team

---

## Release Cadence

| Type | When |
|------|------|
| Planned release | End of roadmap slice / sprint |
| Hotfix | Production severity incidents only |

Prefer small frequent releases over large “big bang” drops.

---

## Versioning

Use SemVer-style tags when shipping:

```
v0.1.0   # early foundation
v0.2.0   # employee
v1.0.0   # first production-ready milestone agreed by product
```

Pre-1.0 may move faster; still tag production deploys.

---

## Release Checklist

### 1. Freeze candidate

- [ ] Target commit on `main` is green on CI
- [ ] Feature flags/settings documented if partial rollout
- [ ] Migrations reviewed ([MIGRATION_RULES.md](../03-database/MIGRATION_RULES.md))

### 2. Staging

- [ ] Deploy candidate to staging
- [ ] Run migrations
- [ ] Smoke test critical paths for the phase (auth, permissions, touched modules)
- [ ] Verify queues/workers and mail (or mail trap)

### 3. Release notes

- [ ] Summarize user-facing changes
- [ ] Note breaking API/schema changes
- [ ] List new permissions that need assignment

### 4. Production

- [ ] Backup DB (and files if needed)
- [ ] Put maintenance window if destructive migration
- [ ] Deploy app
- [ ] Run migrations forward only
- [ ] Restart/reload queue workers
- [ ] Smoke test production
- [ ] Tag release (`vX.Y.Z`)

### 5. Post-release

- [ ] Monitor errors/logs
- [ ] Confirm scheduled jobs
- [ ] Communicate to stakeholders

---

## Hotfix Process

1. Create `hotfix/*` from production tag or `main` as agreed
2. Minimal fix + test
3. Expedited review
4. Deploy staging → production
5. Tag `vX.Y.Z`
6. Ensure fix is on `main`

---

## Rollback

| Layer | Action |
|-------|--------|
| App | Redeploy previous known-good artifact/tag |
| DB | Prefer forward fix; restore from backup only if necessary and approved |
| Feature | Disable via settings/flag when available |

Never `migrate:fresh` on production.

---

## Permissions & Seeds on Release

When a release adds permissions:

- [ ] Seeder/migration path to insert new permission keys
- [ ] Document which roles should receive them
- [ ] Verify `/api/me` returns new keys after deploy

---

## Related Documents

- [DEPLOYMENT.md](./DEPLOYMENT.md)
- [BRANCH_STRATEGY.md](./BRANCH_STRATEGY.md)
- [../09-roadmap/MASTER_ROADMAP.md](../09-roadmap/MASTER_ROADMAP.md)
