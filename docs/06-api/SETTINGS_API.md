# Settings API

> Company and module settings.
>
> Business: [../02-business/settings/README.md](../02-business/settings/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Path

```
/api/settings
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_settings` | Read |
| `can_manage_settings` | Update |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/settings` | Get settings (grouped) |
| `PUT` | `/api/settings` | Update one or more keys |
| `GET` | `/api/settings/{group}` | Group only (`company`, `auth`, `attendance`, …) |

---

## Get settings

`GET /api/settings`

```json
{
  "success": true,
  "data": {
    "company": {
      "timezone": "Asia/Ho_Chi_Minh",
      "locale": "vi",
      "currency": "VND"
    },
    "auth": {
      "session_lifetime_minutes": 120,
      "two_factor_required": false
    }
  }
}
```

---

## Update

`PUT /api/settings`

```json
{
  "company": {
    "timezone": "Asia/Ho_Chi_Minh",
    "currency": "VND"
  }
}
```

Sensitive secret material is never accepted here — use env/secret manager.

Audited when policy requires (e.g. forcing 2FA company-wide).

---

## Related

- [ORGANIZATION_API.md](./ORGANIZATION_API.md)
- [AUTH_API.md](./AUTH_API.md)
