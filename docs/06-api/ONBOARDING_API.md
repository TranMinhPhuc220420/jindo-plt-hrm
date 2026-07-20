# Onboarding API

> Onboarding cases, checklist tasks, probation, completion.
>
> Business: [../02-business/onboarding/README.md](../02-business/onboarding/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/onboarding-cases
/api/onboarding-tasks
/api/onboarding-templates
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_onboarding` | View cases |
| `can_manage_onboarding` | Create/configure |
| `can_complete_onboarding_task` | Complete assigned tasks |
| `can_complete_onboarding` | Mark case completed |
| `can_manage_onboarding_templates` | Templates |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/onboarding-cases` | List cases |
| `POST` | `/api/onboarding-cases` | Start case (from offer or manual) |
| `GET` | `/api/onboarding-cases/{id}` | Detail + progress |
| `POST` | `/api/onboarding-cases/{id}/complete` | Complete onboarding |
| `GET` | `/api/onboarding-cases/{id}/tasks` | Tasks |
| `POST` | `/api/onboarding-tasks/{id}/complete` | Complete task |
| `POST` | `/api/onboarding-tasks/{id}/reopen` | Reopen task (HR) |
| `GET` | `/api/onboarding-templates` | Templates |
| `POST` | `/api/onboarding-templates` | Create template |
| `PATCH` | `/api/onboarding-templates/{id}` | Update template |

Equipment assignment uses [ASSET_API.md](./ASSET_API.md); documents use [DOCUMENT_API.md](./DOCUMENT_API.md); account creation provisions a `User` with `EMPLOYEE_DEFAULT_PASSWORD` (see [EMPLOYEE_API.md](./EMPLOYEE_API.md) Account password).

---

## Start case

`POST /api/onboarding-cases`

```json
{
  "offer_id": 15,
  "employee_id": 10,
  "template_id": 1,
  "probation_ends_on": "2026-11-01"
}
```

`offer_id` optional if HR starts manually after foundation phase.

---

## Case detail

```json
{
  "success": true,
  "data": {
    "id": 3,
    "status": "in_progress",
    "employee_id": 10,
    "progress": { "done": 4, "total": 7, "mandatory_remaining": 2 },
    "probation_ends_on": "2026-11-01",
    "tasks": [
      {
        "id": 31,
        "key": "create_account",
        "title": "Create user account",
        "mandatory": true,
        "status": "done",
        "assignee_type": "hr"
      }
    ]
  }
}
```

---

## Complete case

`POST /api/onboarding-cases/{id}/complete`

Fails with `ONBOARDING_MANDATORY_PENDING` if mandatory tasks remain.  
On success: employee steady-state confirmation via Employee service; audited.

---

## Error Codes

| Code | When |
|------|------|
| `ONBOARDING_MANDATORY_PENDING` | Cannot complete |
| `ONBOARDING_ALREADY_COMPLETED` | Duplicate complete |
| `ONBOARDING_TASK_NOT_ASSIGNED` | Wrong actor |

---

## Related

- [RECRUITMENT_API.md](./RECRUITMENT_API.md)
- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
- [ASSET_API.md](./ASSET_API.md)
- [DOCUMENT_API.md](./DOCUMENT_API.md)
