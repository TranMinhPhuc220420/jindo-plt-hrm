# Asset API

> Inventory, assignment, return, maintenance, damage, replacement.
>
> Business: [../02-business/asset/README.md](../02-business/asset/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/assets
/api/asset-assignments
/api/asset-maintenances
/api/asset-damage-reports
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_assets` | Inventory |
| `can_manage_assets` | CRUD assets |
| `can_assign_asset` | Assign |
| `can_return_asset` | Return |
| `can_report_asset_damage` | Damage reports |
| `can_manage_asset_maintenance` | Maintenance |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/assets` | List inventory |
| `POST` | `/api/assets` | Create asset |
| `GET` | `/api/assets/{id}` | Detail + current assignment |
| `PATCH` | `/api/assets/{id}` | Update |
| `POST` | `/api/assets/{id}/retire` | Retire |
| `POST` | `/api/assets/{id}/assign` | Assign to employee |
| `POST` | `/api/assets/{id}/return` | Return to inventory |
| `GET` | `/api/asset-assignments` | Assignment history |
| `GET` | `/api/assets/{id}/maintenances` | Maintenance list |
| `POST` | `/api/assets/{id}/maintenances` | Add maintenance |
| `POST` | `/api/assets/{id}/damage-reports` | File damage |
| `POST` | `/api/assets/{id}/replace` | Replacement flow |

Assign/return are **audited**.

---

## Create asset

`POST /api/assets`

```json
{
  "code": "LAP-0042",
  "name": "MacBook Pro 14",
  "category": "laptop",
  "status": "available",
  "serial_number": "C02…"
}
```

Statuses: `available` | `assigned` | `maintenance` | `retired` | `lost` | …

---

## Assign / Return

`POST /api/assets/{id}/assign`

```json
{
  "employee_id": 10,
  "assigned_at": "2026-07-16",
  "note": "Onboarding kit"
}
```

`POST /api/assets/{id}/return`

```json
{
  "returned_at": "2026-12-01",
  "condition": "good",
  "note": "Exit process"
}
```

Unavailable asset → `ASSET_NOT_AVAILABLE`.

---

## Damage report

`POST /api/assets/{id}/damage-reports`

```json
{
  "description": "Cracked screen",
  "reported_at": "2026-07-16",
  "document_ids": [55]
}
```

Evidence files via Documents module.

---

## Error Codes

| Code | When |
|------|------|
| `ASSET_NOT_AVAILABLE` | Cannot assign |
| `ASSET_ALREADY_ASSIGNED` | Dual custody blocked |
| `ASSET_INVALID_STATUS` | Illegal transition |

---

## Related

- [ONBOARDING_API.md](./ONBOARDING_API.md)
- [DOCUMENT_API.md](./DOCUMENT_API.md)
- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
