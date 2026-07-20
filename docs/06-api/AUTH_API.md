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
| `POST` | `/api/auth/forgot-password` | Guest | Request reset link/token |
| `POST` | `/api/auth/reset-password` | Guest | Reset with token |
| `GET` | `/api/me` | Yes | Current user + permissions + locale |
| `PUT` | `/api/me/locale` | Yes | Set personal locale preference (`vi` \| `en` \| `null`) |
| `POST` | `/api/auth/two-factor/enable` | Yes | Start 2FA enrollment |
| `POST` | `/api/auth/two-factor/confirm` | Yes | Confirm 2FA setup |
| `POST` | `/api/auth/two-factor/challenge` | Guest/partial | Complete 2FA during login |
| `DELETE` | `/api/auth/two-factor` | Yes | Disable 2FA (re-auth may be required) |

Rate-limit login, forgot-password, and 2FA challenge.

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
    "company_locale": "vi"
  }
}
```

| Field | Meaning |
|-------|---------|
| `locale` | Effective locale for UI (`user_locale ?? company_locale ?? app default`) |
| `user_locale` | Personal preference; `null` = follow company |
| `company_locale` | From settings `company.locale` |

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

## Forgot / Reset Password

`POST /api/auth/forgot-password`

```json
{ "email": "user@example.test" }
```

Always return a generic success message (no account enumeration).

`POST /api/auth/reset-password`

```json
{
  "token": "…",
  "email": "user@example.test",
  "password": "new-password",
  "password_confirmation": "new-password"
}
```

---

## Logout

`POST /api/auth/logout` → **200** with empty/null `data` or message.

---

## Error Codes (examples)

| Code | When |
|------|------|
| `AUTH_INVALID_CREDENTIALS` | Login failed |
| `AUTH_TWO_FACTOR_REQUIRED` | Challenge needed |
| `AUTH_TWO_FACTOR_INVALID` | Bad 2FA code |
| `AUTH_RESET_TOKEN_INVALID` | Bad/expired reset token |

---

## Related

- [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md)
- [../05-frontend/API_CLIENT.md](../05-frontend/API_CLIENT.md)
