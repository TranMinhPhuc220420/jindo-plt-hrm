# Modal Guideline

> Dialogs, drawers, and confirmations.
>
> Elevation Z-2: [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)

---

## Modal vs Drawer vs Page

| Pattern | Use |
|---------|-----|
| Modal (center dialog) | Confirmations, small forms, approve/reject notes |
| Drawer (side panel) | Medium edit forms without leaving list context |
| Full page | Large creates (employee, payroll run setup, onboarding) |

If a form has many sections/tabs, prefer a page over a mega-modal.

---

## Anatomy

```
Overlay (dimmed)
  Panel (white, large radius, strong shadow)
    Header: title · optional subtitle · close
    Body: content / form
    Footer: Secondary · Primary (destructive variant if needed)
```

---

## Behavior Rules

1. Trap focus while open; restore focus on close
2. Escape and overlay click close **unless** dirty form — then confirm discard
3. Primary action shows loading and disables double-submit
4. On success: close + toast + invalidate list query
5. On 422: keep open and show field errors

---

## Confirmation Modals

Use for:

- Reject leave
- Delete/archive employee
- Finalize payroll
- Force checkout / destructive corrections

Copy must state consequence. Prefer typed confirm only for extreme cases.

| Tone | Button |
|------|--------|
| Neutral confirm | Primary green |
| Destructive | Destructive red (`#ff3b30`) |

---

## Approve / Reject Pattern

```
Modal title: Approve leave request?
Body: summary (employee, dates, type)
Optional note field
Footer: Cancel · Reject · Approve
```

Reject may require a reason field (backend-driven).

---

## FAB → Modal

Stitch dashboard uses a primary green FAB (“+”).  
FAB should open the page’s primary create flow (modal, drawer, or route) — not a random menu of unrelated actions.

---

## Accessibility

- `role="dialog"` + `aria-modal="true"`
- Labelled by title id
- Initial focus on first meaningful control (or primary if confirm-only)
- See [ACCESSIBILITY.md](../07-uiux/ACCESSIBILITY.md)

---

## Anti-Patterns

1. Stacking multiple modals deeply
2. Modal that navigates away without closing
3. Uncloseable modal on error with no message
4. Using modals for full payroll worksheets

---

## Related Documents

- [FORM_GUIDELINE.md](./FORM_GUIDELINE.md)
- [LAYOUT.md](./LAYOUT.md)
- [COMPONENT_GUIDELINE.md](./COMPONENT_GUIDELINE.md)
