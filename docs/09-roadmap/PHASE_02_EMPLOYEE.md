# Phase 02 — Employee

> Employee master data as the spine of the lifecycle.
>
> Master: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)  
> Business: [../02-business/employee/README.md](../02-business/employee/README.md)

---

## Goal

Deliver create/read/update of employees with org placement, status lifecycle, and sensitive-field permissions — ready for attendance, leave, and payroll to reference `employee_id`.

---

## In Scope

- Employee profile CRUD + list/filter
- Org links: branch, department, team, position, manager
- Satellites: emergency contacts, education, work history, family
- Contracts metadata
- Bank / insurance / tax (permission-gated)
- Status transitions (probation, active, suspended, resigned, archived)
- Employee UI list/detail/forms in shell
- Audit on employee edits / status changes

---

## Out of Scope

- Attendance punches, leave, payroll calculation
- Full document binary UX (basic link OK; Documents module in Phase 07)
- Recruitment candidates

---

## Dependencies

- Phase 01 (auth, org, permissions, shell)

---

## Key Docs

- [EMPLOYEE_API.md](../06-api/EMPLOYEE_API.md)
- [../03-database/ERD.md](../03-database/ERD.md) (employee cluster)
- [FORM_GUIDELINE.md](../05-frontend/FORM_GUIDELINE.md)
- [TABLE_GUIDELINE.md](../05-frontend/TABLE_GUIDELINE.md)

---

## Exit Criteria

- [x] HR can create/update employees under company scope
- [x] Sensitive fields hidden without permission
- [x] Status transitions validated + audited
- [x] List filters (status, department, search) work
- [x] API + UI aligned; tests for 403 on sensitive update
- [ ] Tag path toward `v0.2.0` (deferred until release request)
