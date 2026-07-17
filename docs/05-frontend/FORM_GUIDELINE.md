# Form Guideline

> Forms for create/edit/approve flows in the HRM UI.
>
> Validation source of truth: backend Form Requests ([VALIDATION.md](../04-backend/VALIDATION.md))  
> Visual: [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)

---

## Principles

1. Server validates authoritatively; client validates for UX speed.
2. Labels above fields (`label-md`) — not placeholder-only.
3. Map 422 `errors` into field messages.
4. Sensitive fields (salary, bank, tax) respect permissions — hide or read-only.
5. One primary submit action per form form footer.

---

## Field Styling

| State | Style |
|-------|--------|
| Default | bg `#f2f2f7`, radius 12px, no border |
| Focus | white bg, 2px primary green border |
| Error | destructive/error border + helper text |
| Disabled | reduced opacity, not-editable |

---

## Structure

```
Card / Page section
  Section title (optional)
  Field grid (1 col mobile · 2 col desktop when paired)
    Label
    Control
    Hint / Error
  Footer actions: Secondary (Cancel) · Primary (Save)
```

Use cards for logical groups (Profile, Bank, Contract) on employee forms — not one endless unlabeled stack.

---

## Patterns by Flow

| Flow | Pattern |
|------|---------|
| Create entity | Full page or large modal; redirect to detail on success |
| Edit entity | Page or drawer; optimistic UI optional; toast on success |
| Approve / Reject | Modal or inline actions with confirm for reject |
| Multi-step (onboarding) | Stepper; persist per step when API supports |

---

## Client Validation

- Required, email, date order (`end >= start`), file mime/size
- Mirror critical backend rules; do not invent payroll math
- Show errors on submit (and optionally on blur after first submit)

---

## Server Error Mapping

From [ERROR_HANDLING.md](../04-backend/ERROR_HANDLING.md):

```
422 → set field errors from `errors`
403 → toast / banner “not allowed”
401 → redirect login
error_code e.g. LEAVE_BALANCE_INSUFFICIENT → dedicated alert, not only field red
```

---

## Permissions in Forms

```
can_manage_employee_sensitive → show bank/tax fields
can_manage_salary → show salary inputs
can_approve_leave → show approve/reject actions
```

Hide unauthorized fields entirely when possible (cleaner than disabled noise).

---

## Accessibility

- Associate `label` with control `id`
- `aria-invalid` + `aria-describedby` for errors
- Keyboard-submittable; don’t trap focus unexpectedly outside modals

---

## Anti-Patterns

1. Submit button with no loading/disabled state while pending
2. Clearing the whole form on a single field 422
3. Putting business balance checks only in the UI
4. Autocomplete-hostile naming for password fields

---

## Related Documents

- [MODAL_GUIDELINE.md](./MODAL_GUIDELINE.md)
- [API_CLIENT.md](./API_CLIENT.md)
- [../07-uiux/ACCESSIBILITY.md](../07-uiux/ACCESSIBILITY.md)
