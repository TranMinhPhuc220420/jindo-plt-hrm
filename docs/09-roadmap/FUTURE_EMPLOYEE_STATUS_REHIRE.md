# Future — Admin rehire / employee status reactivation

> Backlog id: `∞.employee-status-rehire` in [PROGRESS.md](./PROGRESS.md)  
> API: [EMPLOYEE_API.md](../06-api/EMPLOYEE_API.md)  
> Business: [employee/README.md](../02-business/employee/README.md)

---

## Problem

On `/employees/:id`, an admin (permission `can_change_employee_status`) can pick a new status, including **Active**. Saving **archived → active** (or **resigned → active**) fails with **409** `EMPLOYEE_INVALID_STATUS_TRANSITION`. HR cannot put a former employee back to work on the same master record.

This is not a missing form. The show page already posts to `POST /api/employees/{id}/status`. The backend transition map treats exit statuses as one-way.

---

## Root cause

Allowed transitions live in `app/Services/Employee/EmployeeStatusTransitions.php`:

| From | To (today) |
|------|------------|
| `probation` | `active`, `suspended`, `resigned` |
| `active` | `suspended`, `resigned` |
| `suspended` | `active`, `resigned` |
| `resigned` | `archived` only |
| `archived` | **none** (terminal) |

Same-status posts are allowed (idempotent). `PATCH /api/employees/{id}` does **not** accept `status` (`UpdateEmployeeRequest`).

`EmployeeService::applyTerminationDate()` already clears `terminated_at` for `suspended`/`resigned` → `active`/`probation`, but `resigned → active` is currently illegal, so that clear path is unused. `archived → *` never clears `terminated_at`.

Unit tests encode the terminal rule: `tests/Unit/Services/Employee/EmployeeStatusTransitionsTest.php` (`archived is a terminal state…`, `resigned can only move to archived`).

---

## Goal

Let HR/Admin with `can_change_employee_status` reactivate a visible employee so they can sign in, punch, and receive shift assignments again — **without creating a second employee code**.

Primary path: **`archived` → `active`**. Also allow **`resigned` → `active`** and rehire-as-probation (`archived`/`resigned` → `probation`).

---

## Proposed transition map

Keep exit discipline (no skip from `active`/`probation`/`suspended` straight to `archived`). Add **rehire** edges only:

| From | To (proposed) |
|------|----------------|
| `probation` | `active`, `suspended`, `resigned` *(unchanged)* |
| `active` | `suspended`, `resigned` *(unchanged)* |
| `suspended` | `active`, `resigned` *(unchanged; already the “return from leave” path)* |
| `resigned` | `archived`, **`active`**, **`probation`** |
| `archived` | **`active`**, **`probation`** |

Still illegal (keep 409):

- `probation`/`active`/`suspended` → `archived` (must resign first, except `DELETE` archive — see below)
- `archived` → `suspended` / `resigned`
- `active` → `probation` (not needed for this ticket)

---

## Side effects of reactivation (reuse existing behavior)

| Concern | Behavior |
|---------|----------|
| Login | `EmployeeAccountGate` blocks `suspended`/`resigned`/`archived`. After `active`/`probation`, login and `/api/me` work again. `InvalidateEmployeeSessions` only runs when **entering** a blocked status — no extra work. |
| Attendance | `canPunch()` is `probation` \| `active`. Punch is allowed after reactivation. |
| `terminated_at` | **Must clear** when leaving `resigned`/`archived` for `active`/`probation`. Extend `applyTerminationDate()`. Do not change `hired_at`. |
| Shifts | Offboarding already closes assignments via `ShiftAssignmentService::closeFrom`. **Do not auto-recreate** a shift. Inactive employees still cannot get new assignments (`SHIFT_EMPLOYEE_INACTIVE`) until status is punch-allowed. HR assigns a shift on the existing employee show / shifts UI after reactivation. |
| Assets | Offboarding already requires return / `confirm_asset_return`. Rehire does not reopen assets. |
| Audit | Existing `employee.status_changed` + `EmployeeStatusChanged`. Optional: document `reason` as recommended for rehire (not required). |
| Notifications | `LogEmployeeNotification` treats exit as `resigned`/`archived`/`suspended`. Rehire is a normal `employee.status_changed` to the employee; no extra HR blast required. |
| Permissions | No new key. Admin/HR already have `can_change_employee_status`. |

`effective_on` today is used to close shifts on offboarding. For rehire it can stay optional audit metadata only (do not reopen historical assignments).

---

## Soft-delete vs status `archived` (important)

Two different archive paths exist:

1. **`POST /status` with `status: archived`** — row stays; list/show work. **This is the bug the admin hits.**
2. **`DELETE /api/employees/{id}`** — sets `archived`, then **soft-deletes**. `EmployeeService::find()` uses `findOrFail` **without** `withTrashed` → **404** on `/employees/:id`.

**Out of scope for this ticket:** restoring a soft-deleted row. If product later needs “undelete”, add `withTrashed` + restore in a follow-up.

---

## UI (`resources/js/pages/employees/show.tsx`)

The status `<select>` lists every enum value. After the API change, choosing Active will succeed. Still improve UX so illegal options are not offered:

1. **Filter options** to `current ∪ allowed_next_statuses`.
2. Expose `allowed_next_statuses: string[]` on `EmployeeResource` (from `EmployeeStatusTransitions::ALLOWED[$status]` plus current). Avoid a second hardcoded matrix in TS.
3. Helper copy when current is `resigned`/`archived`: rehire is allowed; assign a shift afterwards; login works again if `user_id` is set.
4. Keep asset-return UI only when the **target** is offboarding (`resigned`/`archived`).
5. i18n `en` + `vi` (`employees.show.*`).

No list-page change required (filter already includes archived).

---

## Tests

- Unit: replace “archived is terminal” / “resigned only to archived”; assert new matrix; keep unknown statuses rejected.
- Feature `EmployeeApiTest`:
  - `archived` → `active` 200; `terminated_at` null; linked user can `GET /api/me`.
  - `resigned` → `active` 200; `terminated_at` null.
  - `archived` → `probation` 200.
  - Still 409: `probation` → `archived`, `active` → `archived`, `archived` → `resigned`.
- Do not weaken existing offboarding tests (shift close, assets, session invalidation).

---

## Docs to update when implementing

- [EMPLOYEE_API.md](../06-api/EMPLOYEE_API.md) — rehire transitions; `allowed_next_statuses`; `terminated_at` cleared on rehire.
- [employee/README.md](../02-business/employee/README.md) — rehire workflow; archive is not an irreversible HR dead-end for non-deleted rows.
- This file: tick the PROGRESS checkboxes and leave a short “shipped” note.

---

## Implementation order

1. `EmployeeStatusTransitions::ALLOWED` + unit tests.
2. `applyTerminationDate()` for `archived` → `active`/`probation`.
3. `EmployeeResource` + `allowed_next_statuses`.
4. Feature tests (status + login).
5. Show page: filter select + helper + i18n.
6. API/business docs + PROGRESS.md.

No migration. No new permission. No new route.

---

## Shipped (2026-09-04)

Implemented as `∞.employee-status-rehire`. `archived`/`resigned` → `active`/`probation` is allowed; `terminated_at` clears; show payload includes `allowed_next_statuses`. Soft-delete restore remains out of scope.

---

## Out of scope

- Soft-delete restore (`DELETE` then show 404).
- New `rehired_at` column or rewriting `hired_at`.
- Auto-creating shift assignments or assets on rehire.
- New employee code / duplicate profile.
- Allowing `active` → `archived` in one step.
- Changing default list filters.
