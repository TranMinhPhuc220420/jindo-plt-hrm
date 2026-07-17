# API Architecture

> REST API architecture for the HRM platform.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)
>
> Endpoint catalogs: `docs/06-api/`

---

## Purpose

Define how clients talk to the backend: style, layering, auth, errors, versioning stance, and module boundaries at the HTTP edge.

---

## Style

- **REST** over HTTPS
- **JSON** request/response bodies
- Resource-oriented URLs
- Standard HTTP methods and status codes

Clients:

- React desktop web
- React mobile web
- Future: React Native, desktop app, public API consumers

---

## Request Pipeline

```
Client
  → HTTPS
    → Middleware (auth, throttle, locale, company context)
      → Route → Controller
        → Form Request (validation + coarse auth)
          → Policy / permission check
            → Service
              → Repository / DB
                → API response envelope
```

Controllers remain thin. They do not contain payroll formulas, leave balance math, or approval workflows.

---

## Resource Ownership by Module

| Module | Example resource families | API doc |
|--------|---------------------------|---------|
| Auth | login, logout, password reset, session, 2FA, `/me` | [AUTH_API.md](../06-api/AUTH_API.md) |
| Authorization | roles, permissions, user role assignments | [ROLES_API.md](../06-api/ROLES_API.md) |
| Organization | companies, branches, departments, teams, positions, org tree | [ORGANIZATION_API.md](../06-api/ORGANIZATION_API.md) |
| Settings | company/module setting groups (read/update) | [SETTINGS_API.md](../06-api/SETTINGS_API.md) |
| Audit | audit log list/detail (**read-only** HTTP; writes internal) | [AUDIT_API.md](../06-api/AUDIT_API.md) |
| Employee | employees, profiles, contracts, related sub-resources | [EMPLOYEE_API.md](../06-api/EMPLOYEE_API.md) |
| Attendance | attendance records, corrections, approvals, summaries | [ATTENDANCE_API.md](../06-api/ATTENDANCE_API.md) |
| Leave | leave types, balances, requests, holidays | [LEAVE_API.md](../06-api/LEAVE_API.md) |
| Shift | shifts, assignments, calendars | [SHIFT_API.md](../06-api/SHIFT_API.md) |
| Payroll | salary inputs, payroll runs, payslips | [PAYROLL_API.md](../06-api/PAYROLL_API.md) |
| Recruitment | jobs, candidates, interviews, offers | [RECRUITMENT_API.md](../06-api/RECRUITMENT_API.md) |
| Onboarding | checklists, tasks, probation | [ONBOARDING_API.md](../06-api/ONBOARDING_API.md) |
| Performance | goals, KPIs/OKRs, review cycles, evaluations | [PERFORMANCE_API.md](../06-api/PERFORMANCE_API.md) |
| Assets | assets, assignments, maintenance | [ASSET_API.md](../06-api/ASSET_API.md) |
| Documents | documents, categories, downloads | [DOCUMENT_API.md](../06-api/DOCUMENT_API.md) |
| Notifications | inbox, preferences, read state | [NOTIFICATION_API.md](../06-api/NOTIFICATION_API.md) |
| Reports | report endpoints / exports, dashboard summary | [REPORT_API.md](../06-api/REPORT_API.md) |

Conventions: [REST_STANDARD.md](../06-api/REST_STANDARD.md). Index: [06-api/README.md](../06-api/README.md).

---

## Authentication & Authorization at the Edge

1. Authenticate the caller ([AUTHENTICATION.md](./AUTHENTICATION.md)).
2. Authorize the action with permissions/policies ([AUTHORIZATION.md](./AUTHORIZATION.md)).
3. Never rely on the UI hiding a button as security.
4. Permission keys are stable strings (e.g. `can_approve_leave`), not role-name conditionals in controllers.

---

## Response & Error Philosophy

- Success responses use a consistent envelope (see `docs/04-backend/API_RESPONSE.md`).
- Validation failures return field-level errors.
- Authorization failures return 401 (unauthenticated) / 403 (forbidden).
- Domain rule violations return clear, machine-usable error codes where helpful.
- Do not leak internal stack traces to clients.

---

## Versioning Stance

v1 internal API may ship without aggressive URL versioning, but:

- Avoid breaking response shapes casually.
- Prefer additive changes.
- When a public API appears (later phase), introduce explicit versioning (`/api/v1/...`) rather than breaking internal consumers silently.

---

## Sync vs Async APIs

| Pattern | Use when |
|---------|----------|
| Synchronous REST | CRUD, approvals, simple reads |
| Accepted + job | Payroll calculation, heavy exports, bulk notifications |
| Event-driven side effects | Audit write, email/push after domain success |

Clients that start long jobs should poll a status endpoint or receive a notification when complete.

See [EVENT_FLOW.md](./EVENT_FLOW.md).

---

## Cross-Module API Rules

- A module’s HTTP API must not become a backdoor into another module’s private tables.
- If Payroll needs attendance totals, the payroll service calls attendance services internally — the client does not stitch forbidden writes.
- Aggregation endpoints for dashboards/reports belong under Reports/Dashboard, consuming services underneath.

---

## Related Documents

- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
- [BACKEND_ARCHITECTURE.md](./BACKEND_ARCHITECTURE.md)
- [AUTHENTICATION.md](./AUTHENTICATION.md)
- [AUTHORIZATION.md](./AUTHORIZATION.md)
- `docs/06-api/REST_STANDARD.md`
- `docs/04-backend/API_RESPONSE.md`
- `docs/04-backend/ERROR_HANDLING.md`
