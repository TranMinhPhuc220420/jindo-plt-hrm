# Repositories

> Persistence and query boundary for HRM modules.
>
> Architecture: [BACKEND_ARCHITECTURE.md](../01-architecture/BACKEND_ARCHITECTURE.md)

---

## Purpose

Repositories isolate how aggregates are loaded and saved. Business policy stays in services; HTTP stays in controllers.

---

## When to Use a Repository

Use a repository when:

- Queries are reused across services
- Persistence logic would clutter a service
- You want a test seam for DB access
- Module boundaries need a clear data API

Eloquent models alone are acceptable for trivial CRUD early on, but non-trivial modules should grow repositories (or dedicated query objects) rather than scattering fat queries in controllers.

---

## Naming & Location

```
app/Repositories/{Module}/{Entity}Repository.php

EmployeeRepository
LeaveRequestRepository
PayrollRunRepository
AttendanceRecordRepository
```

Optional interfaces:

```
app/Repositories/{Module}/Contracts/EmployeeRepositoryInterface.php
```

Bind interfaces in a service provider when multiple implementations exist (e.g. read replica later).

---

## Responsibilities

A repository may:

- Find by id / keys
- List with filters (company, status, date range)
- Save / update / delete (or soft-delete) aggregates it owns
- Eager-load relations needed by its module
- Encapsulate query performance details (indexes assumed per [INDEXING.md](../03-database/INDEXING.md))

A repository must not:

- Authorize users
- Send notifications
- Call other modules’ services as a substitute for query ownership confusion
- Contain payroll formulas / leave approval decisions
- Accept `Request` objects

---

## Ownership Rules

| Rule | Meaning |
|------|---------|
| One module owns the repository | Matches table ownership in [TABLE_RELATIONSHIP.md](../03-database/TABLE_RELATIONSHIP.md) |
| Cross-module reads | Prefer the owning module’s service/query API |
| Cross-module writes | Forbidden via foreign repositories |

Example:

- `PayrollCalculationService` calls `AttendanceSummaryService` (or an attendance read API), not `AttendanceRecordRepository` from payroll code paths if that bypasses attendance invariants.

---

## Query Guidelines

1. Always consider `company_id` scope for company-owned data.
2. Prefer explicit columns/filters over `select *` in heavy lists.
3. Paginate API list endpoints at the service/controller edge using repository methods that support pagination.
4. Keep “summary” queries named clearly (`sumOvertimeHours`, `getMonthlySummary`).
5. Do not hide business branching (`if status === approved`) that belongs in services — repositories can filter by status when asked.

---

## Transactions

Repositories participate in transactions started by services:

```
Service::handle()
  DB::transaction
    repositoryA.save()
    repositoryB.save()
```

Repositories should not start nested business transactions unless they are truly self-contained utilities.

---

## Soft Deletes & Archives

- Use Eloquent soft deletes where conventions require
- Employee archival may be status-driven rather than delete-driven — repositories should support the chosen model consistently
- Default queries should exclude soft-deleted rows unless explicitly including them for admin/history

---

## Testing

- Feature tests hit the DB through the real stack
- Unit tests may mock repositories when testing service rules
- Repository-focused tests are valuable for complex date/company filters

---

## Anti-Patterns

1. `BaseRepository` with 50 generic methods nobody understands
2. Controllers injecting five repositories
3. Repository methods named like use cases (`approveLeave`) — that is service territory
4. Cross-module “helper repository” that joins everything

---

## Related Documents

- [SERVICES.md](./SERVICES.md)
- [LARAVEL_STRUCTURE.md](./LARAVEL_STRUCTURE.md)
- [../03-database/DATABASE_CONVENTIONS.md](../03-database/DATABASE_CONVENTIONS.md)
