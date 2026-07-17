# Roles & Permissions API

> Manage roles, permission keys, and assignments.
>
> Architecture: [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md)  
> Catalog: [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/roles
/api/permissions
/api/users/{id}/roles
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_roles` | List roles/permissions |
| `can_manage_roles` | CRUD roles + assign permissions |
| `can_assign_roles` | Attach roles to users |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/permissions` | List all permission keys |
| `GET` | `/api/roles` | List roles |
| `POST` | `/api/roles` | Create role |
| `GET` | `/api/roles/{id}` | Role + permissions |
| `PATCH` | `/api/roles/{id}` | Update role |
| `PUT` | `/api/roles/{id}/permissions` | Replace permission set |
| `DELETE` | `/api/roles/{id}` | Delete/deactivate role |
| `GET` | `/api/users/{id}/roles` | User’s roles |
| `PUT` | `/api/users/{id}/roles` | Replace user roles |

Effective permissions for the UI still come from `GET /api/me`.

---

## Replace role permissions

`PUT /api/roles/{id}/permissions`

```json
{
  "permissions": [
    "can_view_employee",
    "can_create_employee",
    "can_approve_leave"
  ]
}
```

---

## Rules

1. Permission keys are stable strings from the catalog — do not invent ad hoc keys in controllers.
2. Never authorize by role display name in business code.
3. Seed default roles (Admin, HR, Manager, Employee) in Phase 01.

---

## Related

- [AUTH_API.md](./AUTH_API.md)
- [../03-database/SEEDING.md](../03-database/SEEDING.md)
