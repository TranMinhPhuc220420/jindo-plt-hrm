# API Client

> Typed HTTP client conventions for talking to the Laravel REST API.
>
> Stack: [STACK_DECISION.md](../01-architecture/STACK_DECISION.md) (Sanctum SPA — cookies + CSRF)  
> Auth: [AUTH_API.md](../06-api/AUTH_API.md)  
> Envelope: [API_RESPONSE.md](../04-backend/API_RESPONSE.md)  
> Errors: [ERROR_HANDLING.md](../04-backend/ERROR_HANDLING.md)  
> Architecture: [API_ARCHITECTURE.md](../01-architecture/API_ARCHITECTURE.md)

---

## Responsibilities

The API client:

1. Sends JSON requests with credentials/cookies as required
2. Parses the standard success/error envelope
3. Normalizes errors for UI (field maps, `error_code`, status)
4. Centralizes 401 handling
5. Exposes typed module endpoint helpers

It does not render UI or contain domain approval rules.

---

## Location

```
resources/js/lib/api/
  client.ts           # fetch/axios instance
  types.ts            # ApiSuccess<T>, ApiError
  errors.ts           # normalizeError()
  modules/
    employee.ts
    attendance.ts
    leave.ts
    payroll.ts
    ...
```

---

## Envelope Handling

Success:

```ts
type ApiSuccess<T> = {
  success: true;
  data: T;
  message?: string;
  meta?: PaginationMeta;
};
```

Error:

```ts
type ApiErrorBody = {
  success: false;
  message: string;
  error_code?: string;
  errors?: Record<string, string[]>;
};
```

Helpers should return `data` to callers on success and throw a normalized error on failure.

---

## Auth

- Attach session/cookie or bearer per [AUTHENTICATION.md](../01-architecture/AUTHENTICATION.md)
- CSRF header when cookie-based SPA auth requires it
- On **401**: clear client auth + redirect `/login`
- On **403**: surface forbidden state to the feature (do not logout by default)

---

## Module API Helpers

```ts
// leave.ts
export function getLeaveRequests(params: LeaveListParams) { ... }
export function approveLeave(id: number, body?: { note?: string }) { ... }
```

Naming: verb + resource; mirror REST paths from `docs/06-api/`.

---

## Async Jobs (202)

For exports / long payroll steps:

1. Client receives `202` + `job_id` / status
2. Poll status endpoint or wait for notification
3. Present download link when ready

Do not block the UI thread on multi-minute work.

---

## File Upload / Download

- Upload via `FormData` to Documents endpoints
- Download via authorized API or short-lived signed URL
- Never assume public bucket URLs

See [FILE_STORAGE.md](../01-architecture/FILE_STORAGE.md).

---

## Error UX Mapping

| Status / code | UI |
|---------------|-----|
| 422 | Field errors on form |
| 403 | Toast / 403 panel |
| 404 | Not found panel |
| 409 / domain `error_code` | Alert with message |
| 429 | “Too many attempts” / retry later (`TOO_MANY_REQUESTS`) |
| 500 | Generic error + retry (`SERVER_ERROR`) |
| 502 / 503 | Service unavailable + retry (`BAD_GATEWAY` / `SERVICE_UNAVAILABLE`) |

Attendance check-in/out: always send `Idempotency-Key`; on infra/network failure queue locally and retry with the **same** key.

---

## Typing

- Share DTO types under `resources/js/types/` or per module
- Keep enums aligned with backend status strings
- Prefer generating types later if OpenAPI appears; until then hand-maintained types are fine

---

## Anti-Patterns

1. `fetch` scattered in random components with different error shapes
2. Swallowing errors silently
3. Storing tokens in `localStorage` when HttpOnly cookie session is available
4. Calling multiple write APIs from the UI to fake a transaction the backend should own

---

## Related Documents

- [STATE_MANAGEMENT.md](./STATE_MANAGEMENT.md)
- [FORM_GUIDELINE.md](./FORM_GUIDELINE.md)
- [../06-api/REST_STANDARD.md](../06-api/REST_STANDARD.md)
