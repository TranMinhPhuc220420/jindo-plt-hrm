# Deployment

> Runtime topology and deploy steps for the HRM platform.
>
> Release flow: [RELEASE_PROCESS.md](./RELEASE_PROCESS.md)

---

## Components

| Component | Role |
|-----------|------|
| Web/App (Laravel) | HTTP API + (optional) web entry |
| Queue worker(s) | Notifications, exports, payroll post-steps |
| Scheduler | Reminders, cron-like jobs (`schedule:run`) |
| MySQL | Primary datastore |
| File storage | Local disk (dev) / object storage (prod) |
| Cache/session | Redis or database driver as configured |
| Frontend assets | Vite build served by app or CDN |

---

## Environments

| Env | Purpose | Data |
|-----|---------|------|
| Local | Development | Disposable / seeded demo |
| Staging | Pre-prod validation | Anonymized or synthetic |
| Production | Live | Real company data — protect PII |

Each environment has its own `.env` secrets — never share production credentials to local commits.

---

## Required Config (high level)

- `APP_KEY`, `APP_ENV`, `APP_URL`
- Database connection
- Queue connection + worker processes
- Mailer (or log/trap in non-prod)
- Filesystem disk (private for employee docs)
- Session/cookie security (`SESSION_SECURE_COOKIE` in HTTPS envs)

Align auth mode with [AUTHENTICATION.md](../01-architecture/AUTHENTICATION.md).

---

## Standard Deploy Steps

```
1. Enable maintenance mode (if needed)
2. Pull/build release artifact
3. Install PHP/Node deps (CI-built artifact preferred)
4. Build frontend assets (vite build)
5. Run php artisan migrate --force
6. Cache config/routes/views as appropriate
7. Restart PHP-FPM / Octane / container
8. Restart queue workers (restart signal)
9. Disable maintenance mode
10. Smoke test
```

Exact commands depend on hosting (VPS, container, Forge, etc.) — keep a runbook per environment.

---

## Workers & Scheduler

| Process | Command (typical) |
|---------|-------------------|
| Queue | `php artisan queue:work` (supervisor/systemd) |
| Scheduler | cron: `* * * * * php artisan schedule:run` |

After deploy, workers must reload code (graceful restart).  
Queues used: `default`, `notifications`, `payroll`, `exports` (see [QUEUES.md](../04-backend/QUEUES.md)).

---

## Migrations in Deploy

- Only forward migrations in production
- Test on staging with production-like data volume when altering large tables
- Backup before destructive or heavy locks
- See [MIGRATION_RULES.md](../03-database/MIGRATION_RULES.md)

---

## File Storage

- Private disk for employee/company documents
- Ensure web server cannot serve storage raw without auth
- Object storage credentials via env

---

## Observability

Minimum:

- Application error logging
- Failed jobs table/monitor
- Uptime check on `/up` or health endpoint
- Alert on 5xx spikes after release

---

## Security Hardening (prod)

- HTTPS only
- Debug off (`APP_DEBUG=false`)
- Least-privilege DB user
- Restrict admin tooling
- Rate-limit auth endpoints
- Regular dependency updates

---

## Smoke Test Script (per release)

At least:

1. Login + `/api/me` permissions
2. One read + one write in each shipped module touched by the release
3. Queue notification or fake mail assertion on staging
4. File upload/download if Documents changed
5. Payroll finalize path on staging only with demo data

---

## Related Documents

- [RELEASE_PROCESS.md](./RELEASE_PROCESS.md)
- [../04-backend/QUEUES.md](../04-backend/QUEUES.md)
- [../01-architecture/FILE_STORAGE.md](../01-architecture/FILE_STORAGE.md)
