# Phase 08 — Insight

> Performance, reports, notifications, and audit visibility.
>
> **Filename note:** `PHASE_08_PERFORMANCE.md` is historical; this phase is **Insight** (PROJECT_LOGIC Phase 5), not performance-only.
>
> Master: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)

---

## Goal

Add structured performance management, cross-domain reporting/exports, reliable notifications, and usable audit trails — without making Reports the write path for domain data.

---

## In Scope

### Performance

- Review cycles
- Goals / KPI / OKR (as configured)
- Evaluations
- Promotion suggestions (advisory only)

### Reports

- Attendance, leave, payroll, employee, department, performance reports
- Async exports
- Dashboard summary endpoint for Stitch-style KPIs

### Notifications

- In-app inbox + read state
- Preferences
- Event-driven email (queued) for key domain events

### Audit

- Ensure required actions are logged
- Basic audit query UI for authorized admins/HR

---

## Out of Scope

- AI performance analysis / AI assistant (Future)
- Full BI warehouse (Future)
- Workflow builder platform (Future)
- Native mobile app (Future)

---

## Dependencies

- Phase 02+ domain modules that reports will read
- Queue workers from Foundation
- Preferably Phases 03–07 for rich report coverage (reports can land incrementally per available domain)

---

## Key Docs

- [PERFORMANCE_API.md](../06-api/PERFORMANCE_API.md)
- [REPORT_API.md](../06-api/REPORT_API.md)
- [NOTIFICATION_API.md](../06-api/NOTIFICATION_API.md)
- [AUDIT_API.md](../06-api/AUDIT_API.md)
- [EVENT_FLOW.md](../01-architecture/EVENT_FLOW.md)
- [../02-business/performance/README.md](../02-business/performance/README.md)
- [../02-business/report/README.md](../02-business/report/README.md)
- [../02-business/notification/README.md](../02-business/notification/README.md)
- [../02-business/audit/README.md](../02-business/audit/README.md)
- [ROUTING.md](../05-frontend/ROUTING.md) (`/audit-logs`, `/reports`, `/notifications`, `/performance`)

---

## Exit Criteria

- [x] Review cycle can be run end-to-end
- [x] At least core reports work with permission gates (incl. payroll sensitivity)
- [x] Export job 202 → ready download on staging
- [x] Inbox shows leave/payroll (and other) events
- [x] Audit entries visible for salary/leave/attendance/asset/employee critical actions
- [x] Dashboard summary feeds Overview KPIs
- [x] Milestone toward `v0.6.0` / discuss `v1.0.0` readiness
