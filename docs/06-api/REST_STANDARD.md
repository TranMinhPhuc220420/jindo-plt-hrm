# REST Standard

> Shared conventions for all HRM REST endpoints.
>
> Architecture: [API_ARCHITECTURE.md](../01-architecture/API_ARCHITECTURE.md)  
> Envelope: [API_RESPONSE.md](../04-backend/API_RESPONSE.md)  
> Errors: [ERROR_HANDLING.md](../04-backend/ERROR_HANDLING.md)

---

## Base URL

```
/api
```

Optional future versioning:

```
/api/v1
```

v1 may ship under `/api` without a version segment; prefer additive changes. Public API later should use explicit `/api/v1`.

---

## Style

| Rule | Convention |
|------|------------|
| Protocol | HTTPS |
| Format | JSON (`Content-Type: application/json`) |
| Resources | Plural nouns: `/employees`, `/leave-requests` |
| IDs | Numeric or string path params: `/employees/{id}` |
| Nested only when ownership is clear | `/employees/{id}/contracts` |
| Actions | Prefer resource state change; else `POST .../approve` |
| Fields | `snake_case` JSON keys |
| Dates | ISO-8601 (`2026-07-16`, `2026-07-16T08:30:00Z`) |
| Money | Decimal strings or numbers with fixed scale — never binary floats in docs/contracts |

---

## HTTP Methods

| Method | Use |
|--------|-----|
| `GET` | Read (list/detail) |
| `POST` | Create or non-idempotent action |
| `PUT` / `PATCH` | Update (`PATCH` preferred for partial) |
| `DELETE` | Delete / archive (soft-delete preferred for master data) |

---

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 202 | Accepted (async job started) |
| 204 | No content (if project standardizes empty deletes) |
| 400 | Bad request |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not found |
| 409 | Conflict / invalid state |
| 422 | Validation error |
| 429 | Rate limited |
| 500 | Server error |

---

## Success Envelope

```json
{
  "success": true,
  "message": "Optional",
  "data": {},
  "meta": {}
}
```

List pagination `meta`:

```json
{
  "current_page": 1,
  "per_page": 20,
  "total": 100,
  "last_page": 5
}
```

---

## Error Envelope

```json
{
  "success": false,
  "message": "Human-readable summary",
  "error_code": "LEAVE_BALANCE_INSUFFICIENT",
  "errors": {
    "start_date": ["The start date is invalid."]
  }
}
```

---

## Auth

- Protected routes require authenticated session/token
- Permission-first authorization on every mutating and sensitive read endpoint
- `/api/me` (or equivalent) returns user + permission list for UI

See [AUTH_API.md](./AUTH_API.md) and [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md).

---

## Common Query Params (lists)

| Param | Meaning |
|-------|---------|
| `page` | Page number |
| `per_page` | Page size (cap server-side) |
| `search` | Text search |
| `sort` | Field to sort |
| `order` | `asc` \| `desc` |
| `status` | Status filter |
| `company_id` | Only for multi-company admin cases; usually implied by session |

Company scope is enforced server-side from the authenticated context.

---

## Action Endpoints

For domain verbs that are not clean CRUD:

```
POST /api/leave-requests/{id}/approve
POST /api/leave-requests/{id}/reject
POST /api/attendance/check-in
POST /api/payroll-runs/{id}/finalize
```

Keep verbs consistent across modules: `approve`, `reject`, `cancel`, `finalize`, `assign`, `return`.

---

## Idempotency & Conflicts

- Finalized payroll / approved leave: further illegal transitions → `409` + `error_code`
- Unique violations → `422` or `409` with clear message
- Async jobs should be safe to retry where possible

---

## File Endpoints

- Upload: `multipart/form-data`
- Download: authorized stream or short-lived URL in `data`
- Never expose permanent public paths for private employee files

---

## Module Index

| Doc | Module |
|-----|--------|
| [AUTH_API.md](./AUTH_API.md) | Authentication / session / me |
| [ORGANIZATION_API.md](./ORGANIZATION_API.md) | Company / org tree |
| [ROLES_API.md](./ROLES_API.md) | Roles & permissions |
| [SETTINGS_API.md](./SETTINGS_API.md) | Settings |
| [AUDIT_API.md](./AUDIT_API.md) | Audit log reads |
| [EMPLOYEE_API.md](./EMPLOYEE_API.md) | Employees |
| [ATTENDANCE_API.md](./ATTENDANCE_API.md) | Attendance |
| [LEAVE_API.md](./LEAVE_API.md) | Leave |
| [SHIFT_API.md](./SHIFT_API.md) | Shifts |
| [PAYROLL_API.md](./PAYROLL_API.md) | Payroll |
| [RECRUITMENT_API.md](./RECRUITMENT_API.md) | Recruitment |
| [ONBOARDING_API.md](./ONBOARDING_API.md) | Onboarding |
| [PERFORMANCE_API.md](./PERFORMANCE_API.md) | Performance |
| [ASSET_API.md](./ASSET_API.md) | Assets |
| [DOCUMENT_API.md](./DOCUMENT_API.md) | Documents |
| [NOTIFICATION_API.md](./NOTIFICATION_API.md) | Notifications |
| [REPORT_API.md](./REPORT_API.md) | Reports |

Full list: [README.md](./README.md). Permissions: [../01-architecture/PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md).
