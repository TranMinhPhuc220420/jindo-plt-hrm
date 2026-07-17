# State Management

> Client state strategy for the HRM React app.
>
> Architecture: [FRONTEND_ARCHITECTURE.md](../01-architecture/FRONTEND_ARCHITECTURE.md)

---

## Principles

1. **Server state** comes from the API and may be cached.
2. **UI state** (modals, selected rows, wizard step) stays local when possible.
3. **Auth session** (user + permissions) is global but read-only from UI’s perspective.
4. No global store for payroll/leave **business rules**.
5. Prefer simple tools; add libraries only when caching/invalidation needs them.

---

## State Categories

| Category | Examples | Where |
|----------|----------|--------|
| Auth session | user, permissions, employee_id | Global auth context / store |
| Server lists/details | employees, leave requests | API cache (React Query / SWR / equivalent) or page loaders |
| UI ephemeral | modal open, tab index | `useState` / component state |
| URL state | filters, page, sort | Router query params |
| Notifications unread | badge count | Lightweight global or polled query |

---

## Recommended Pattern

```
Page / Feature
  → useQuery / api hook (server data)
  → local useState (UI)
  → PermissionGate (from auth permissions)
  → Mutations → invalidate queries → toast
```

If the project uses Inertia for some pages, keep domain HRM data flowing through the documented REST client for API-first modules — do not fork competing sources of truth for the same entity.

---

## Auth State

Loaded from login / `/me`:

```ts
{
  user: { id, name, email },
  permissions: string[],
  employee_id?: number
}
```

Helpers:

```ts
can('can_approve_leave')
canAny([...])
```

On 401: clear auth state → redirect login.

---

## Cache Invalidation

| Mutation | Invalidate |
|----------|------------|
| Create employee | employees list |
| Approve leave | leave lists + balances + notifications |
| Check-in | attendance today/summary + dashboard KPIs |
| Finalize payroll | payroll runs + payslips |

Be explicit — avoid “invalidate everything” on each click.

---

## Forms State

- Prefer controlled fields or a form library
- Keep dirty tracking for modal discard confirms
- Do not store form drafts in a giant global store unless offline is a goal

---

## Dashboard Data

KPI cards, attendance chart, activity feed:

- Fetch via dedicated dashboard/report endpoints when available
- Each widget can load independently (show per-widget skeletons)
- Polling only where freshness matters (notifications); otherwise refresh on focus/navigate

---

## Anti-Patterns

1. Duplicating leave balance in three stores
2. `useEffect` fetch waterfalls without loading UX
3. Context that re-renders the whole app on every keystroke
4. Encoding approval workflows only in client state

---

## Related Documents

- [API_CLIENT.md](./API_CLIENT.md)
- [ROUTING.md](./ROUTING.md)
- [FORM_GUIDELINE.md](./FORM_GUIDELINE.md)
