# Organization API

> Company, branches, departments, teams, positions.
>
> Business: [../02-business/organization/README.md](../02-business/organization/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/companies
/api/branches
/api/departments
/api/teams
/api/positions
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_organization` | List/show |
| `can_manage_organization` | Mutate org nodes |
| `can_manage_company` | Company update |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/companies/current` | Current company (v1) |
| `PATCH` | `/api/companies/current` | Update company profile |
| `GET` | `/api/branches` | List |
| `POST` | `/api/branches` | Create |
| `GET` | `/api/branches/{id}` | Show |
| `PATCH` | `/api/branches/{id}` | Update |
| `DELETE` | `/api/branches/{id}` | Soft-delete / deactivate |
| `GET` | `/api/departments` | List (`branch_id`) |
| `POST` | `/api/departments` | Create |
| `PATCH` | `/api/departments/{id}` | Update |
| `DELETE` | `/api/departments/{id}` | Soft-delete |
| `GET` | `/api/teams` | List (`department_id`) |
| `POST` | `/api/teams` | Create |
| `PATCH` | `/api/teams/{id}` | Update |
| `DELETE` | `/api/teams/{id}` | Soft-delete |
| `GET` | `/api/positions` | List org positions |
| `POST` | `/api/positions` | Create |
| `PATCH` | `/api/positions/{id}` | Update |
| `DELETE` | `/api/positions/{id}` | Soft-delete |
| `GET` | `/api/organization/tree` | Nested tree for UI |

---

## Create department (example)

`POST /api/departments`

```json
{
  "branch_id": 1,
  "name": "Engineering",
  "code": "ENG"
}
```

---

## Notes

- `positions` here are **org chart** titles — recruitment uses `job_openings`.
- Company scope enforced from session; multi-company admin listing is future SaaS.

---

## Related

- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
- [SETTINGS_API.md](./SETTINGS_API.md)
