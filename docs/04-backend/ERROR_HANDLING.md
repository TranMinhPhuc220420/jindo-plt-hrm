# Error Handling

> How the backend maps failures to stable HTTP/API errors.
>
> Responses: [API_RESPONSE.md](./API_RESPONSE.md)

---

## Goals

1. Clients can branch on HTTP status + machine-usable error codes
2. Validation errors are field-addressable
3. Security: no stack traces or secrets in production responses
4. Domain rule failures are distinct from infrastructure failures

---

## Error Envelope

```json
{
  "success": false,
  "message": "Human-readable summary",
  "error_code": "LEAVE_BALANCE_INSUFFICIENT",
  "errors": {
    "start_date": ["The start date is invalid."]
  },
  "meta": {}
}
```

| Field | Meaning |
|-------|---------|
| `success` | Always `false` for errors |
| `message` | Safe summary for UI toast/banner |
| `error_code` | Optional stable machine code for domain errors |
| `errors` | Optional field map (validation) |
| `meta` | Optional extras (request id, etc.) |

---

## HTTP Status Mapping

| Status | Use |
|--------|-----|
| 400 | Malformed request / generic bad input outside field validation |
| 401 | Unauthenticated |
| 403 | Authenticated but forbidden (policy/permission) |
| 404 | Resource not found (or not visible in scope — avoid leaking cross-company existence when required) |
| 409 | Conflict (duplicate run, invalid state transition race) |
| 422 | Validation failed (Form Request) |
| 429 | Rate limited (`TOO_MANY_REQUESTS`) |
| 500 | Unexpected server error (`SERVER_ERROR`) |
| 502 | Bad gateway (`BAD_GATEWAY`) |
| 503 | Dependency / service unavailable (`SERVICE_UNAVAILABLE`) |

When an HTTP exception exposes `Retry-After`, include it in the error envelope as `meta.retry_after` (seconds or HTTP-date).

---

## Exception Strategy

| Layer | Throw | Mapped to |
|-------|-------|-----------|
| Form Request | ValidationException | 422 + field errors |
| Policy / Gate | AuthorizationException | 403 |
| Auth | AuthenticationException | 401 |
| Domain service | DomainException / custom (`LeaveBalanceInsufficientException`) | 409/422 + `error_code` |
| Not found | ModelNotFoundException / custom | 404 |
| Unknown | Throwable | 500 + logged |

Register mappings in Laravel’s exception handler so controllers stay clean.

---

## Domain Error Codes (examples)

Use UPPER_SNAKE_CASE, stable across releases:

| Code | Meaning |
|------|---------|
| `LEAVE_BALANCE_INSUFFICIENT` | Not enough balance |
| `LEAVE_INVALID_TRANSITION` | Bad status change |
| `ATTENDANCE_PERIOD_LOCKED` | Cannot correct/approve |
| `IDEMPOTENCY_KEY_REQUIRED` | Punch missing `Idempotency-Key` header |
| `IDEMPOTENCY_KEY_INVALID` | Punch `Idempotency-Key` is not a UUID |
| `IDEMPOTENCY_KEY_REUSE` | Same key used with a different punch body |
| `SERVICE_UNAVAILABLE` | HTTP 503 |
| `BAD_GATEWAY` | HTTP 502 |
| `PAYROLL_ALREADY_FINALIZED` | Immutable run |
| `ASSET_NOT_AVAILABLE` | Cannot assign |
| `COMPANY_SCOPE_MISMATCH` | Cross-company reference |

Add codes when clients must distinguish handling; do not invent codes for every trivial validation message.

---

## Logging & Audit

- Log 500s with stack traces server-side
- Do **not** report `DomainException` (expected domain rule failures) — they are rendered as API envelopes only. Reporting them floods ERROR logs and can leak sensitive call-stack arguments (e.g. passwords on failed login).
- Log authorization anomalies as needed (security)
- Business audit logs are separate from error logs (see audit conventions)
- Include request id/correlation id in logs when available

---

## Security Rules

1. Never return SQL/Eloquent internals to clients
2. Never return env secrets
3. Prefer generic 404 for out-of-scope resources when leaking existence is sensitive
4. Auth endpoints must not reveal whether an email exists (forgot password)

---

## Frontend Expectations

- 401 → clear session / redirect login
- 403 → show forbidden state
- 422 → map `errors` into form fields
- Domain `error_code` → optional special UI (insufficient balance modal, etc.)

---

## Testing

- Feature tests assert status + `error_code` / field keys for critical flows
- Ensure production debug mode does not expose traces in tests that simulate prod config when relevant

---

## Anti-Patterns

1. `return response()->json(['error' => $e->getMessage()], 500)` everywhere
2. Using 200 OK with `success: false` for domain failures
3. Catching all exceptions in controllers and silencing them
4. Different error shapes per module

---

## Related Documents

- [API_RESPONSE.md](./API_RESPONSE.md)
- [VALIDATION.md](./VALIDATION.md)
- [POLICIES.md](./POLICIES.md)
- [../06-api/REST_STANDARD.md](../06-api/REST_STANDARD.md)
