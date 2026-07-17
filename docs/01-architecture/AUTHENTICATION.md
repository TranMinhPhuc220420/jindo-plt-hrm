# Authentication

> Identity and session architecture for the HRM platform.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) §6 Authentication
>
> API details: `docs/06-api/AUTH_API.md`

---

## Purpose

Define how users prove identity and how sessions are managed. Authentication answers **who you are**. Authorization answers **what you may do** — see [AUTHORIZATION.md](./AUTHORIZATION.md).

---

## Responsibilities

From project logic:

- Login
- Logout
- Forgot Password
- Reset Password
- Two-factor Authentication (2FA)
- Remember Login
- Session Management

---

## Actors

| Actor | Notes |
|-------|-------|
| Employee user | Linked to an employee record when applicable |
| HR / Manager / Admin users | Same auth stack; differ by permissions |
| System | May run jobs without a browser session; not a substitute for user audit actor when a user triggered the action |

A user account is not the same thing as an employee profile, but they are usually linked after onboarding/account creation.

---

## High-Level Flow

### Login

```
Client submits credentials
  → Validate input
    → Verify identity
      → (Optional) challenge 2FA
        → Establish session / issue auth token
          → Return user identity + permission summary for UI
```

### Logout

```
Authenticated request
  → Invalidate session / revoke token
    → Clear client auth state
```

### Forgot / Reset Password

```
Request reset (identity lookup without account enumeration leakage)
  → Issue time-limited reset token
    → User submits new password
      → Invalidate reset token + relevant sessions as policy requires
```

---

## Session Strategy (architecture)

**Decision (locked):** Laravel **Sanctum SPA authentication** for the first-party web app.

| Client | Mechanism |
|--------|-----------|
| Desktop / Mobile Web (same origin or configured SPA domain) | Sanctum cookie session + CSRF (`X-XSRF-TOKEN` / Axios defaults) |
| Future native mobile / third-party | Sanctum API tokens (Bearer), same users/permissions |

See [STACK_DECISION.md](./STACK_DECISION.md).

Requirements:

- Transport only over HTTPS in non-local environments
- Session cookie: `HttpOnly`, `Secure` (prod), `SameSite` appropriate for SPA setup
- CSRF protection required for cookie-based mutating requests
- Idle / absolute session lifetime configurable via settings
- Remember-login extends persistence explicitly; default sessions stay shorter
- Do **not** store long-lived access tokens in `localStorage` for the web SPA
- Fortify (or equivalent) may implement password/2FA **behind** [AUTH_API.md](../06-api/AUTH_API.md) — the public contract remains REST

---

## Two-Factor Authentication

- 2FA is part of the authentication module, not a separate product.
- Enrollment and challenge flows are authenticated/reset-protected as appropriate.
- Backup/recovery policy must be defined before enforcing 2FA company-wide.
- 2FA success is required before establishing a full session when enabled for the user/company.

---

## Security Rules

1. Never store plaintext passwords.
2. Password reset tokens are single-use and expiring.
3. Auth endpoints are rate-limited against brute force.
4. Do not reveal whether an email/username exists in forgot-password responses.
5. Authentication alone never grants business access — every protected resource still requires authorization.
6. Important auth events may be audited (login failure spikes, password reset, 2FA changes) according to security needs.

---

## Client Responsibilities

- Store auth state securely (prefer HttpOnly cookie session over long-lived tokens in `localStorage` when possible).
- Attach credentials on API calls as required by the chosen auth mode.
- On 401, clear client session view and route to login.
- Never embed secrets in the frontend bundle.

See [FRONTEND_ARCHITECTURE.md](./FRONTEND_ARCHITECTURE.md).

---

## Relationship to Other Modules

```
Authentication
  → Authorization (roles/permissions loaded after identity is known)
    → Organization / Employee / … (business modules)
```

Onboarding may create user accounts; Authentication owns credential lifecycle after creation.

---

## Out of Scope Here

- Role/permission model → [AUTHORIZATION.md](./AUTHORIZATION.md)
- Endpoint request/response schemas → `docs/06-api/AUTH_API.md`
- Social login / SSO / SAML — future expansion unless explicitly scheduled

---

## Related Documents

- [AUTHORIZATION.md](./AUTHORIZATION.md)
- [API_ARCHITECTURE.md](./API_ARCHITECTURE.md)
- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
- `docs/06-api/AUTH_API.md`
