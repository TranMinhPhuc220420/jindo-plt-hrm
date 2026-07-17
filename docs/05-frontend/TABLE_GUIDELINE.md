# Table Guideline

> List/table UX for HRM modules (employees, leave, attendance, payroll runs, …).
>
> Visual: white card, 16px radius — [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)

---

## When to Use a Table

| Use table | Prefer cards/list |
|-----------|-------------------|
| Dense comparable rows | Mobile-first short timelines |
| Sort/filter/bulk actions | Activity feeds (Stitch timeline) |
| Admin/HR queues | Dashboard KPI widgets |

On mobile, tables may switch to stacked row cards.

---

## Anatomy

```
Card
  Toolbar: title · filters · primary CTA
  Table
    Header row (sortable columns optional)
    Body rows (click → detail)
    Empty / loading / error states
  Footer: pagination meta
```

---

## Column Rules

1. Lead with identity (name, code) not opaque IDs
2. Status as Badge (pending/approved/rejected/active)
3. Dates formatted consistently (locale-aware)
4. Money columns only if `can_view_salary` (or equivalent)
5. Actions column: icon buttons or overflow menu; permission-gated
6. Avoid more than ~7–8 visible columns without horizontal scroll strategy

---

## Row Interaction

| Action | Behavior |
|--------|----------|
| Click row | Navigate to detail (unless clicking a control) |
| Checkbox | Bulk select when bulk API exists |
| Hover | Subtle background, not heavy shadow |

---

## Filters

- Place above table inside the same card or a bar directly above
- Common filters: status, department, date range, search
- Sync important filters to URL query ([ROUTING.md](./ROUTING.md))
- “Reset filters” control when any active

---

## Sorting & Pagination

- Prefer server-side sort/page for large datasets
- Show `meta.current_page`, `total`, `per_page` from API envelope
- Default page size stable (e.g. 20)

---

## Status Badges

| Status | Visual |
|--------|--------|
| Approved / Active / Success | Green tint + dark green text |
| Pending | Neutral / warning orange |
| Rejected / Error | Red / destructive |
| Draft | Muted gray |

---

## Loading / Empty

- Loading: skeleton rows (preferred) inside card
- Empty: message + optional CTA (“Create employee”)
- Error: inline alert + Retry

---

## Permissions

- Hide salary/bank columns without permission
- Hide approve actions without `can_approve_*`
- Row actions menu only lists permitted operations

---

## Anti-Patterns

1. Rendering 10k rows client-side without virtualization/paging
2. Tables without empty states
3. Destructive actions without confirm
4. Mixing chart widgets into table cells densely

---

## Related Documents

- [FORM_GUIDELINE.md](./FORM_GUIDELINE.md)
- [COMPONENT_GUIDELINE.md](./COMPONENT_GUIDELINE.md)
- [API_CLIENT.md](./API_CLIENT.md)
