# Auth API

> Login, session, password, 2FA, and current user.
>
> Business: [AUTHENTICATION.md](../01-architecture/AUTHENTICATION.md)  
> Stack: [STACK_DECISION.md](../01-architecture/STACK_DECISION.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Auth mode (locked)

| Item | Choice |
|------|--------|
| Web SPA | Laravel Sanctum **SPA** (session cookie + CSRF) |
| Future mobile | Sanctum **API tokens** |
| Implementation helpers | Fortify may power password/2FA internally |

Client setup (web):

1. `GET /sanctum/csrf-cookie` (or framework equivalent) before login if required
2. `POST /api/auth/login` with credentials (`credentials: 'include'` / `withCredentials`)
3. Subsequent `/api/*` calls send session cookie + CSRF header on mutations

---

## Base Paths

```
/api/auth/*
/api/me
```

Paths below are the **stable product contract**. Map Fortify/Sanctum internals to these routes (or thin aliases) — do not invent a second parallel auth API for the SPA.

---

## Endpoints

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/api/auth/login` | Guest | Login with credentials (+ optional 2FA step) |
| `POST` | `/api/auth/logout` | Yes | Invalidate session/token |
| `GET` | `/api/me` | Yes | Current user + permissions + locale |
| `PUT` | `/api/me/locale` | Yes | Set personal locale preference (`vi` \| `en` \| `null`) |
| `POST` | `/api/auth/two-factor/enable` | Yes | Start 2FA enrollment |
| `POST` | `/api/auth/two-factor/confirm` | Yes | Confirm 2FA setup |
| `POST` | `/api/auth/two-factor/challenge` | Guest/partial | Complete 2FA during login |
| `DELETE` | `/api/auth/two-factor` | Yes | Disable 2FA (re-auth may be required) |

Self-serve registration and forgot/reset password are **disabled**. Accounts are provisioned by admins; authenticated users change passwords from settings.

Rate-limit login and 2FA challenge.

---

## Login

`POST /api/auth/login`

```json
{
  "email": "admin@example.test",
  "password": "secret",
  "remember": true
}
```

**200** — session established:

```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "Alex Rodriguez", "email": "admin@example.test" },
    "permissions": ["can_view_employee", "can_approve_leave"],
    "employee_id": 10,
    "two_factor_required": false
  }
}
```

If 2FA required, return a challenge state (still authenticated only after challenge succeeds):

```json
{
  "success": true,
  "data": {
    "two_factor_required": true,
    "challenge_token": "…"
  }
}
```

**401/422** — invalid credentials (do not reveal which field was wrong beyond standard validation).

Linked employee in `suspended` / `resigned` / `archived` → **403** `AUTH_ACCOUNT_INACTIVE` after a correct password (does not leak “wrong password”). Users **without** an employee profile can still sign in. Authenticated `/api/*` routes (except logout) re-check eligibility so an already-open session is blocked after HR changes status.

---

## Current User

`GET /api/me`

```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "Alex Rodriguez", "email": "admin@example.test" },
    "permissions": ["can_create_employee", "can_approve_leave", "can_view_salary"],
    "employee_id": 10,
    "locale": "vi",
    "user_locale": null,
    "company_locale": "vi",
    "vapid_public_key": null
  }
}
```

| Field | Meaning |
|-------|---------|
| `locale` | Effective locale for UI (`user_locale ?? company_locale ?? app default`) |
| `user_locale` | Personal preference; `null` = follow company |
| `company_locale` | From settings `company.locale` |
| `vapid_public_key` | Web Push VAPID public key (`null` if not configured). Not a secret. |

UI builds menus from `permissions`. SPA syncs `react-i18next` from `locale` — see [I18N.md](../05-frontend/I18N.md).

---

## Update personal locale

`PUT /api/me/locale`

```json
{ "locale": "en" }
```

| `locale` value | Effect |
|----------------|--------|
| `"vi"` / `"en"` | Persist preference on `users.locale` |
| `null` | Clear preference; follow company default |

**200** — same shape as `/api/me` (updated `locale` / `user_locale`).

**422** — invalid locale (not `vi`, `en`, or `null`).

No special permission: any authenticated user updates **own** preference only.

Company-wide default remains `PUT /api/settings` (`can_manage_settings`).

---

## Logout

`POST /api/auth/logout` → **200** with empty/null `data` or message.

---

## Error Codes (examples)

| Code | When |
|------|------|
| `AUTH_INVALID_CREDENTIALS` | Login failed |
| `AUTH_ACCOUNT_INACTIVE` | Linked employee is `suspended`, `resigned`, or `archived` (403). Users without an employee profile can still sign in. |
| `AUTH_TWO_FACTOR_REQUIRED` | Challenge needed |
| `AUTH_TWO_FACTOR_INVALID` | Bad 2FA code |

---

## Related

- [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md)
- [../05-frontend/API_CLIENT.md](../05-frontend/API_CLIENT.md)
