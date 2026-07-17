# Phase 04 — Leave

> Leave types, balances, requests, holidays, weekend rules.
>
> Master: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)  
> Business: [../02-business/leave/README.md](../02-business/leave/README.md)

---

## Goal

Let employees request time off with balance checks and manager/HR approval, including holidays and half-day/hourly variants as scoped.

---

## In Scope

- Leave types configuration
- Balances + manual adjust (permissioned)
- Request / cancel / approve / reject
- Holidays + weekend rules
- Half-day / hourly / compensation leave (per MVP depth agreed)
- Notifications hooks on request/decision (channel can be system-first)
- Audit on reject (and approve as policy)

---

## Out of Scope

- Platform-wide workflow builder (Future)
- Payroll unpaid-leave posting (Payroll consumes leave in Phase 06)

---

## Dependencies

- Phase 02 Employee
- Phase 05 Shift / working calendar (helpful for validation)

---

## Key Docs

- [LEAVE_API.md](../06-api/LEAVE_API.md)
- [NOTIFICATION_API.md](../06-api/NOTIFICATION_API.md)
- [../02-business/leave/README.md](../02-business/leave/README.md)

---

## Exit Criteria

- [ ] Request → approve/reject updates balance correctly
- [ ] `LEAVE_BALANCE_INSUFFICIENT` returned when applicable
- [ ] Holidays block/affect validation per rules
- [ ] Permission `can_approve_leave` enforced with relationship scope
- [ ] Tests for overlap and illegal transitions
