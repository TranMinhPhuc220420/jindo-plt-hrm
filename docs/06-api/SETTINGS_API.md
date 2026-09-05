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

### Company locale

| Key | Allowed | Default |
|-----|---------|---------|
| `company.locale` | `vi`, `en` | `vi` |

Invalid values → **422** (`SETTINGS_LOCALE_INVALID` or validation error).

Company locale is the default for users with `users.locale = null`. Personal override: [AUTH_API.md](./AUTH_API.md) `PUT /api/me/locale`.

### Attendance punch reminders

| Key | Default | Meaning |
|-----|---------|---------|
| `attendance.punch_reminder_enabled` | `true` | Scheduler may send missed punch reminders |
| `attendance.punch_reminder_check_in_grace_minutes` | `5` | Minutes after shift start before a check-in reminder |
| `attendance.punch_reminder_check_out_grace_minutes` | `10` | Minutes after shift end before a check-out reminder |

Timezone for due windows is `company.timezone`.

---

## Update

`PUT /api/settings`

```json
{
  "company": {
    "timezone": "Asia/Ho_Chi_Minh",
    "locale": "vi",
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
