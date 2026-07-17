# Laravel Structure

> Backend folder layout and ownership conventions for the HRM API.
>
> Architecture: [BACKEND_ARCHITECTURE.md](../01-architecture/BACKEND_ARCHITECTURE.md)

---

## Purpose

Keep the Laravel app navigable by module and layer: thin HTTP edge, services for business rules, repositories for persistence.

---

## Target Layout

```
app/
  Actions/                 # Single-purpose invokable units (see ACTIONS.md)
  Concerns/                # Shared traits (sparingly)
  Console/Commands/        # Artisan commands / scheduled tasks
  Domain/                  # Optional: domain grouping by module (preferred as project grows)
    Employee/
      Models/
      Services/
      Repositories/
      Events/
      Policies/            # or keep Policies under app/Policies with module prefix
  Enums/
  Events/
  Exceptions/
  Http/
    Controllers/
      Api/
        V1/                # when versioning is introduced
        Employee/
        Attendance/
        ...
    Middleware/
    Requests/
      Employee/
      Attendance/
      ...
    Resources/             # API transformers if used
  Jobs/
  Listeners/
  Models/                  # Acceptable early; migrate toward Domain/* as modules grow
  Notifications/
  Policies/
  Providers/
  Repositories/
  Services/
    Employee/
    Attendance/
    Leave/
    Payroll/
    ...
  Support/                 # Pure helpers with no business ownership

bootstrap/
config/
database/
  migrations/
  seeders/
  factories/
routes/
  api.php                  # REST API routes (primary for SPA/mobile)
  web.php                  # web/session routes as needed
  console.php
tests/
  Feature/
  Unit/
```

Early project stages may keep models under `app/Models` and services under `app/Services/{Module}`. As modules grow, prefer clearer module boundaries (`app/Domain/{Module}` or `app/Modules/{Module}`) without breaking dependency rules.

---

## Layer Mapping

| Concern | Location |
|---------|----------|
| Routes | `routes/api.php` (+ module route files if split) |
| Controllers | `app/Http/Controllers/...` |
| Validation | `app/Http/Requests/...` |
| Policies | `app/Policies/...` |
| Services | `app/Services/{Module}/...` |
| Actions | `app/Actions/{Module}/...` |
| Repositories | `app/Repositories/{Module}/...` |
| Models | `app/Models` or `app/Domain/{Module}/Models` |
| Events / Listeners / Jobs | `app/Events`, `app/Listeners`, `app/Jobs` |
| API Resources | `app/Http/Resources/...` |
| Tests | `tests/Feature/{Module}`, `tests/Unit/{Module}` |

---

## Controller Rules

Controllers:

1. Accept a Form Request
2. Authorize via policy/request
3. Call a Service or Action
4. Return a consistent API response

Controllers must not:

- Contain payroll/leave/attendance formulas
- Query unrelated modules’ tables
- Dispatch five side effects instead of completing a use case in a service

---

## Module Ownership Checklist

For each business module (Employee, Attendance, Leave, …):

- [ ] Routes grouped and named
- [ ] Controllers + Form Requests
- [ ] Policies / permissions
- [ ] Services (and Actions where appropriate)
- [ ] Models + repositories/queries
- [ ] Events for important outcomes
- [ ] Feature/unit tests

Cross-module calls: **service → service** only.

---

## Routing Conventions

- Prefer REST resource routes per [API_ARCHITECTURE.md](../01-architecture/API_ARCHITECTURE.md)
- Prefix API routes (`/api` or `/api/v1`)
- Protect with auth + throttle middleware
- Keep route files thin; no business logic in closures for domain operations

---

## Config & Env

- Secrets only in `.env` / secret manager — never committed
- Feature flags / company defaults prefer settings tables or config with clear ownership
- Queue, mail, filesystem disks configured via Laravel config files

---

## Compatibility Note

The starter kit may include Inertia/Fortify web scaffolding. HRM domain features should still expose and obey the **REST API architecture** documented in `docs/01-architecture/` and `docs/06-api/`, so web and future mobile clients share one contract.

---

## Related Documents

- [SERVICES.md](./SERVICES.md)
- [ACTIONS.md](./ACTIONS.md)
- [REPOSITORIES.md](./REPOSITORIES.md)
- [VALIDATION.md](./VALIDATION.md)
- [POLICIES.md](./POLICIES.md)
- [TESTING.md](./TESTING.md)
