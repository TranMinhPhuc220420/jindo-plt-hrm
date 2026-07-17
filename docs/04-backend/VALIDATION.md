# Validation

> Input validation conventions using Laravel Form Requests.
>
> Principles: [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)

---

## Purpose

Validation belongs in **Form Requests** (or dedicated validator objects), not in controllers or React as the source of truth. The UI may mirror rules for UX; the server always enforces them.

---

## Location & Naming

```
app/Http/Requests/{Module}/{Action}{Entity}Request.php

StoreEmployeeRequest
UpdateEmployeeRequest
ApproveLeaveRequest
CheckInAttendanceRequest
FinalizePayrollRunRequest
```

---

## Form Request Responsibilities

A Form Request should:

1. Declare rules()
2. Optionally authorize() via policy/permission
3. Provide messages()/attributes() for clear API errors
4. Expose validated()/safe DTO data to the controller

A Form Request must not:

- Persist data
- Dispatch domain events
- Call foreign module repositories for heavy workflows (light `exists` rules are OK)
- Contain payroll calculation logic

---

## Rule Design Guidelines

| Topic | Guidance |
|-------|----------|
| Company scope | Ensure referenced FKs belong to current company where applicable |
| Dates | Leave/attendance ranges must be coherent (`end >= start`) |
| Enums/status | Restrict to allowed transitions at the edge when possible |
| Money | Numeric/decimal rules; no floats in API contracts |
| Files | mime, max size; Documents module owns deeper checks |
| Unique codes | `unique:employees,code,...,company_id` style scoping |
| Partial updates | `sometimes` / explicit patch rules |

Domain invariants that require loaded aggregates (balance sufficiency, shift overlap policy) may be validated again in services — Form Requests handle structural/input correctness first.

---

## Authorization in Requests

Prefer:

```php
public function authorize(): bool
{
    return $this->user()->can('can_create_employee');
    // or Gate/Policy against route model
}
```

Keep policy-heavy resource checks in Policies; Request `authorize()` delegates to them.

---

## Sharing Rules

- Extract repeated rule fragments to rule objects (`app/Rules/...`) when reused
- Do not create a giant `BaseRequest` that knows every module
- Keep module-specific rules close to the module

---

## API Error Shape

Validation failures return HTTP **422** with field errors consistent with [ERROR_HANDLING.md](./ERROR_HANDLING.md) and [API_RESPONSE.md](./API_RESPONSE.md).

Frontend maps these into form fields ([../05-frontend/FORM_GUIDELINE.md](../05-frontend/FORM_GUIDELINE.md) when filled).

---

## Service-Level Validation

Use service checks for:

- Leave balance after concurrent requests
- Payroll finalize preconditions
- Attendance correction against locked periods
- Status transition graphs

Throw domain exceptions (not raw 500s) — see [ERROR_HANDLING.md](./ERROR_HANDLING.md).

---

## Testing

- Feature tests cover 422 cases for critical endpoints
- Unit-test custom Rule classes
- Include company-scope negative cases (ID from another company)

---

## Anti-Patterns

1. `$request->validate([...])` scattered inside services
2. Trusting frontend-only validation
3. Validation rules that embed role names instead of permissions for authz
4. Accepting mass-assignment arrays without explicit rule allowlists

---

## Related Documents

- [POLICIES.md](./POLICIES.md)
- [API_RESPONSE.md](./API_RESPONSE.md)
- [ERROR_HANDLING.md](./ERROR_HANDLING.md)
- [../06-api/REST_STANDARD.md](../06-api/REST_STANDARD.md)
