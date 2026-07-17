# API Response

> Consistent JSON response envelope for the HRM REST API.
>
> Architecture: [API_ARCHITECTURE.md](../01-architecture/API_ARCHITECTURE.md)
>
> Errors: [ERROR_HANDLING.md](./ERROR_HANDLING.md)

---

## Goals

1. Predictable client parsing across web/mobile
2. Clear separation of data vs metadata
3. Stable field names; additive evolution preferred
4. Works with Laravel API Resources / transformers

---

## Success Envelope

Default success shape:

```json
{
  "success": true,
  "message": "Optional human-readable message",
  "data": {},
  "meta": {}
}
```

| Field | Required | Meaning |
|-------|----------|---------|
| `success` | yes | `true` for successful outcomes |
| `data` | yes | Payload (`object`, `array`, or `null`) |
| `message` | no | Short UI-usable message |
| `meta` | no | Pagination, filters, extras |

Controllers should build responses via a shared helper/responder — not ad-hoc arrays in every method.

---

## Collection + Pagination Meta

```json
{
  "success": true,
  "data": [
    { "id": 1 }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

Use consistent pagination keys across modules.

---

## Resource Transformation

Prefer API Resources / dedicated transformers for:

- Employee, LeaveRequest, AttendanceRecord, Payslip, etc.
- Hiding sensitive fields unless permitted (`can_view_salary`, bank fields, …)
- Stable date formats (ISO-8601 strings)

Do not expose internal-only columns (`remember_token`, 2FA secrets, storage disks paths if not needed).

---

## Create / Update Responses

| Action | HTTP | Body |
|--------|------|------|
| Create | 201 | Created resource in `data` |
| Update | 200 | Updated resource in `data` |
| Delete / archive | 200 or 204 | Empty or status payload; be consistent project-wide |
| Action (approve) | 200 | Updated resource |

If 204 is used, clients must not expect a body.

---

## Async Accepted Responses

For queued work (exports, heavy payroll steps):

```json
{
  "success": true,
  "message": "Export queued",
  "data": {
    "job_id": "…",
    "status": "queued"
  }
}
```

HTTP **202 Accepted** when appropriate. Provide a status endpoint or notification when ready.

---

## Auth / Me Payload

Login/`/me` should return identity + permission summary for UI menu/feature visibility — not a substitute for server-side policy checks.

```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "…", "email": "…" },
    "permissions": ["can_approve_leave", "can_view_salary"],
    "employee_id": 10
  }
}
```

---

## Consistency Rules

1. One envelope style for all domain endpoints
2. Do not return bare Eloquent models with shifting shapes
3. Prefer snake_case JSON keys unless an established exception exists
4. Null vs omission: be consistent within a resource
5. Errors use the error envelope — see [ERROR_HANDLING.md](./ERROR_HANDLING.md)

---

## Related Documents

- [ERROR_HANDLING.md](./ERROR_HANDLING.md)
- [VALIDATION.md](./VALIDATION.md)
- [../06-api/REST_STANDARD.md](../06-api/REST_STANDARD.md)
- [../01-architecture/API_ARCHITECTURE.md](../01-architecture/API_ARCHITECTURE.md)
