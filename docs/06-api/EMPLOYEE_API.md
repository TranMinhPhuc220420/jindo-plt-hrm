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
| `can_update_employee` | update (non-sensitive) |
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

---

## Update / Status

`PATCH /api/employees/{id}` — partial profile fields.  
`POST /api/employees/{id}/status`

```json
{ "status": "active", "reason": "Probation completed" }
```

Statuses (logical): `probation`, `active`, `suspended`, `resigned`, `archived`, …

Invalid transitions → `409` + `error_code`.

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
| `COMPANY_SCOPE_MISMATCH` | Cross-company reference |

---

## Related

- [DOCUMENT_API.md](./DOCUMENT_API.md)
- [ONBOARDING_API.md](./ONBOARDING_API.md)
- [PAYROLL_API.md](./PAYROLL_API.md)
