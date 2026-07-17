# Backend Architecture

> Laravel backend structure, layering, and module boundaries.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)

---

## Purpose

Define how the Laravel backend is organized so controllers stay thin, business rules live in services, and modules remain loosely coupled.

Folder-level conventions: `docs/04-backend/LARAVEL_STRUCTURE.md`.

---

## Stack

| Item | Choice |
|------|--------|
| Framework | Laravel |
| Language | PHP |
| Database | MySQL |
| API | REST JSON |
| Async | Queues + listeners |
| Auth | Session/token-based auth (see [AUTHENTICATION.md](./AUTHENTICATION.md)) |
| Authz | Roles, permissions, policies (see [AUTHORIZATION.md](./AUTHORIZATION.md)) |

---

## Layer Responsibilities

```
HTTP Request
  → Route / Middleware (auth, throttle, company context)
    → Controller (thin)
      → Form Request (validation)
        → Application / Domain Service (business rules)
          → Repository (persistence)
            → Eloquent / Query Builder → MySQL
```

Side effects (notifications, audit, cross-module reactions) go through Events → Listeners / Jobs.

| Layer | Does | Does not |
|-------|------|----------|
| Controller | Map HTTP ↔ service calls, return API responses | Own business rules, query complex domain logic |
| Form Request | Validate and authorize input shape | Persist data |
| Policy | Answer “can this user do X on resource Y?” | Orchestrate use cases |
| Application Service | Orchestrate a use case across domain services | Speak HTTP |
| Domain Service | Enforce module business rules | Reach into another module’s tables directly |
| Repository | Load/save aggregates, queries | Decide business policy |
| Listener / Job | React asynchronously | Become a second place for core rules |

---

## Module Layout (logical)

Each business domain should map to a clear backend ownership boundary:

```
Auth / Authorization / Organization / Employee
Attendance / Leave / Shift / Payroll
Recruitment / Onboarding / Performance
Assets / Documents / Notifications / Reports
Settings / System / Audit
```

Recommended ownership per module:

- Controllers / routes for that module’s resources
- Form Requests
- Policies
- Services
- Repositories (or Eloquent models owned by the module)
- Events / listeners specific to the module
- Tests for the module

Cross-module calls: **service → service**, never controller → foreign repository.

---

## Dependency Direction (backend)

Follow [DEPENDENCY_RULES.md](./DEPENDENCY_RULES.md):

```
Authentication
  → Authorization
    → Organization
      → Employee
        → Attendance / Leave / Shift / Payroll / Performance / Assets / Documents
          → Reports → Dashboard
```

Examples:

- Payroll may call AttendanceService / LeaveService for calculation inputs.
- Attendance must not import Payroll models or repositories.
- Reports may read via reporting services/queries; domain modules must not depend on Reports.

---

## Replaceable Implementations

Design services so current implementations can be swapped:

| Module | v1 implementation | Future behind same service boundary |
|--------|-------------------|-------------------------------------|
| Attendance | Manual check-in/out | GPS, face, fingerprint, QR providers |
| Payroll | Monthly salary calculator | Hourly / daily / commission / piece-rate strategies |

Prefer strategy/provider interfaces at the service boundary when a future variant is already known.

---

## Multi-company Readiness

Even if v1 has one company:

- Prefer scoping queries by `company_id` (or equivalent) where the resource belongs to a company.
- Do not hardcode “global singleton company” into domain services.
- Authorization and policies should be able to consider company context later without rewrite.

---

## Cross-Cutting Backend Concerns

| Concern | Approach |
|---------|----------|
| Validation | Form Requests |
| Authorization | Policies + permission checks |
| Audit | Emit audit records on important mutations |
| Notifications | Events → notification jobs |
| Files | Storage service; see [FILE_STORAGE.md](./FILE_STORAGE.md) |
| Errors | Consistent API error envelope; see `docs/04-backend/ERROR_HANDLING.md` |
| Transactions | Service-level DB transactions for multi-step use cases |

---

## Testing Expectations

- Module services are independently testable.
- Policies covered for critical actions.
- Controllers tested lightly (HTTP contract); heavy logic tested at service level.
- No circular module dependencies in the test graph.

Details: `docs/04-backend/TESTING.md`.

---

## Related Documents

- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
- [API_ARCHITECTURE.md](./API_ARCHITECTURE.md)
- [EVENT_FLOW.md](./EVENT_FLOW.md)
- `docs/04-backend/SERVICES.md`
- `docs/04-backend/REPOSITORIES.md`
- `docs/04-backend/POLICIES.md`
