# Employee API

> Employee master data and satellites.
>
> Business: [../02-business/employee/README.md](../02-business/employee/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Path

```
/api/employees
```

---

## Permissions (illustrative)

| Permission | Endpoints |
|------------|-----------|
| `can_view_employee` | list, show |
| `can_create_employee` | store |
| `can_update_employee` | update (non-sensitive), password set/reset |
| `can_manage_employee_sensitive` | bank/tax/insurance fields |
| `can_change_employee_status` | status / archive |
| `can_view_own_profile` | self show/update limited fields |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/employees` | Paginated list |
| `POST` | `/api/employees` | Create |
| `GET` | `/api/employees/{id}` | Detail |
| `PATCH` | `/api/employees/{id}` | Update |
| `POST` | `/api/employees/{id}/status` | Change status |
| `DELETE` | `/api/employees/{id}` | Soft-delete / archive (or prefer status) |
| `GET` | `/api/employees/{id}/contracts` | Contracts |
| `POST` | `/api/employees/{id}/contracts` | Add contract |
| `GET` | `/api/employees/{id}/emergency-contacts` | Emergency contacts |
| `PUT` | `/api/employees/{id}/emergency-contacts` | Replace/update contacts |
| `GET` | `/api/employees/{id}/educations` | Educations |
| `GET` | `/api/employees/{id}/family-members` | Family |
| `GET` | `/api/employees/{id}/bank-account` | Bank (sensitive) |
| `PUT` | `/api/employees/{id}/bank-account` | Update bank (sensitive) |
| `GET` | `/api/employees/{id}/tax-profile` | Tax (sensitive) |
| `PUT` | `/api/employees/{id}/tax-profile` | Update tax (sensitive) |
| `PUT` | `/api/employees/{id}/password` | Set or reset linked user password |
| `POST` | `/api/employees/{id}/avatar` | Upload / replace avatar (multipart) |
| `DELETE` | `/api/employees/{id}/avatar` | Remove avatar |
| `POST` | `/api/me/avatar` | Self-service upload (linked employee required) |
| `DELETE` | `/api/me/avatar` | Self-service remove |

Avatar authorization: linked employee (self) **or** `can_update_employee`. Stored on the `public` disk; run `php artisan storage:link` so `/storage/...` URLs resolve.

---

## List

`GET /api/employees?search=&status=&department_id=&page=`

```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "code": "E-0010",
      "full_name": "Jane Doe",
      "status": "active",
      "department": { "id": 3, "name": "Engineering" },
      "position": { "id": 7, "name": "Engineer" },
      "manager_id": 2
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 1248, "last_page": 63 }
}
```

---

## Create

`POST /api/employees`

```json
{
  "code": "E-0125",
  "first_name": "Jane",
  "last_name": "Doe",
  "email": "jane@example.test",
  "department_id": 3,
  "position_id": 7,
  "branch_id": 1,
  "team_id": 2,
  "manager_id": 2,
  "hired_at": "2026-08-01",
  "status": "probation"
}
```

**201** → employee resource. Audited.  
Does **not** create a login `User` — that happens during onboarding (`create_account` task) using `EMPLOYEE_DEFAULT_PASSWORD`.

---

## Update / Status

`PATCH /api/employees/{id}` — partial profile fields.  
`POST /api/employees/{id}/status`

```json
{
  "status": "resigned",
  "reason": "Nghỉ việc 24/08/2026",
  "effective_on": "2026-08-24",
  "confirm_asset_return": true
}
```

| Field | Required | Notes |
|-------|----------|--------|
| `status` | yes | `probation`, `active`, `suspended`, `resigned`, `archived` |
| `reason` | no | Audit note, max 500 |
| `effective_on` | no | Date used to close shift assignments. Defaults to today (company timezone). |
| `confirm_asset_return` | no | Required `true` when moving to `resigned` / `archived` while assets are still assigned |

Show payload includes `terminated_at`, `outstanding_assets`, and `outstanding_assets_count`.

Statuses (logical): `probation`, `active`, `suspended`, `resigned`, `archived`.

Invalid transitions → `409` `EMPLOYEE_INVALID_STATUS_TRANSITION`.

`suspended`, `resigned`, and `archived` block login and kick other sessions. `resigned` / `archived` also:

- set `terminated_at` (cleared when returning `suspended` → `active`)
- close future shift assignments from `effective_on` (delete not-yet-started; set `end_date` on running assignments)
- require outstanding assets to be confirmed returned (`409` `EMPLOYEE_HAS_OUTSTANDING_ASSETS` + `meta.assets` / `errors.assets` if not)

`suspended` keeps shifts and assets so the employee can return.

`DELETE /api/employees/{id}` archives (soft-delete). Outstanding assets still block with `409` — use `POST /status` with `confirm_asset_return` first.

---

## Account password

`PUT /api/employees/{id}/password` — requires `can_update_employee` and a linked `user_id`.

Reset to env default (`EMPLOYEE_DEFAULT_PASSWORD` / `config('hrm.employee_default_password')`):

```json
{ "use_default": true }
```

Or set a custom password:

```json
{
  "password": "new-password",
  "password_confirmation": "new-password"
}
```

Never returns the password value. Audited as `employee.password_reset_to_default` or `employee.password_changed`.

---

## Avatar

`POST /api/employees/{id}/avatar` — multipart field `avatar` (JPEG/PNG/WebP, max 2MB).  
`DELETE /api/employees/{id}/avatar`

Self-service (requires linked employee, else `422` + `EMPLOYEE_NOT_LINKED`):

- `POST /api/me/avatar`
- `DELETE /api/me/avatar`

Response includes `avatar_url`. `/api/me` and Inertia `auth.user` expose the same URL as `user.avatar`. Audited as `employee.avatar_updated` / `employee.avatar_deleted`.

---

## Sensitive Fields

Bank/tax/insurance omitted from default show unless permitted.  
Salary belongs to Payroll API — do not embed payslip math here.

---

## Error Codes

| Code | When |
|------|------|
| `EMPLOYEE_CODE_DUPLICATE` | Code unique per company |
| `EMPLOYEE_INVALID_STATUS_TRANSITION` | Illegal status change |
| `EMPLOYEE_HAS_OUTSTANDING_ASSETS` | Resign/archive while assets are still assigned |
| `EMPLOYEE_NO_USER_ACCOUNT` | Password change without linked user |
| `EMPLOYEE_DEFAULT_PASSWORD_MISSING` | Default password env not configured |
| `EMPLOYEE_NOT_LINKED` | Self avatar without linked employee |
| `AVATAR_INVALID_TYPE` / `AVATAR_TOO_LARGE` | Invalid avatar file |
| `COMPANY_SCOPE_MISMATCH` | Cross-company reference |

---

## Related

- [DOCUMENT_API.md](./DOCUMENT_API.md)
- [ONBOARDING_API.md](./ONBOARDING_API.md)
- [PAYROLL_API.md](./PAYROLL_API.md)
