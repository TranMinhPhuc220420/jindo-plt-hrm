# Master Roadmap

> End-to-end delivery sequence for the HRM platform.
>
> Logic: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) §9  
> Scope: [BUSINESS_SCOPE.md](../00-overview/BUSINESS_SCOPE.md)

---

## Vision Checkpoint

Ship a modular HRM for SMEs covering the employee lifecycle, with permission-first access, auditability, REST API, and Efficient Growth (Stitch) UI — ready for multi-company later without rewrite.

**Living status:** [PROGRESS.md](./PROGRESS.md) — update after every implementation step.

---

## Phase Overview

```
01 Foundation
  → 02 Employee
      → 05 Shift (recommended before full attendance rules)
      → 03 Attendance
      → 04 Leave
          → 06 Payroll
      → 07 Recruitment / Onboarding / Documents / Assets
      → 08 Performance / Reports / Notifications / Audit
          → Future (AI, mobile, public API, SaaS, …)
```

**Note:** Prefer implementing **Shift (05)** before or alongside full Attendance late/OT logic so calendars are real. Manual check-in can start in Phase 03 with simplified rules if needed.

---

## Phase Table

| Phase | Name | Delivers | Depends on | Status |
|-------|------|----------|------------|--------|
| 01 | Foundation | Auth, authz, org, settings, app shell | — | Done |
| 02 | Employee | Employee master + satellites | 01 | Done |
| 03 | Attendance | Punches, corrections, summaries | 02 (05 recommended) | Done |
| 04 | Leave | Types, balances, requests, holidays | 02 (05 helpful) | Done |
| 05 | Shift | Definitions, assignments, calendar, OT rules | 02 | Done |
| 06 | Payroll | Salary, runs, payslips | 02 + 03 (+ 04 unpaid effects) | Done |
| 07 | Recruitment & Ops | Recruitment, onboarding, documents, assets | 02 | Done |
| 08 | Insight | Performance, reports, notifications, audit UX (`PHASE_08_PERFORMANCE.md`) | 02+ domains | Done |
| ∞ | Future | SaaS, devices, AI, mobile, public API | Stable core | Backlog |

Recommended build order inside the time domain: **05 Shift → 03 Attendance → 04 Leave** (file numbers are historical; dependencies matter more than numeric order).

Update Status as phases complete (`Planned` → `In progress` → `Done`).

---

## Global Exit Criteria (every phase)

- [ ] Module boundaries respected
- [ ] Permissions seeded and enforced
- [ ] API docs matched by routes
- [ ] Migrations + indexes in place
- [ ] Feature tests for happy path + 403/422
- [ ] UI follows Efficient Growth shell patterns
- [ ] Important mutations audited where required
- [ ] No secrets in repo

---

## Cross-Phase Platform Work

| Workstream | When |
|------------|------|
| Design system tokens in Tailwind | Phase 01 |
| API envelope + error handler | Phase 01 |
| Audit log writer | Phase 01 (used widely from 02+) |
| Queue workers | Phase 01 setup; heavy use from notifications/payroll |
| Dashboard KPIs | Incremental; polish in Phase 08 / Future |

---

## Suggested Milestone Tags

| Tag | After |
|-----|--------|
| `v0.1.0` | Phase 01 |
| `v0.2.0` | Phase 02 |
| `v0.3.0` | Phase 03–05 (time domain) |
| `v0.4.0` | Phase 06 |
| `v0.5.0` | Phase 07 |
| `v0.6.0` | Phase 08 |
| `v1.0.0` | Product-agreed production readiness |

---

## Risk Register (summary)

| Risk | Mitigation |
|------|------------|
| Building payroll before solid attendance summaries | Enforce dependency; summary contract first |
| Role hardcoding creep | Review checklist + permission seeds |
| UI diverging from Stitch | UI_RULES + design tokens in Phase 01 |
| Big-bang migrations | Additive migrations per phase |

---

## Related Phase Docs

- [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md)
- [PHASE_02_EMPLOYEE.md](./PHASE_02_EMPLOYEE.md)
- [PHASE_03_ATTENDANCE.md](./PHASE_03_ATTENDANCE.md)
- [PHASE_04_LEAVE.md](./PHASE_04_LEAVE.md)
- [PHASE_05_SHIFT.md](./PHASE_05_SHIFT.md)
- [PHASE_06_PAYROLL.md](./PHASE_06_PAYROLL.md)
- [PHASE_07_RECRUITMENT.md](./PHASE_07_RECRUITMENT.md)
- [PHASE_08_PERFORMANCE.md](./PHASE_08_PERFORMANCE.md)
- [FUTURE_FEATURES.md](./FUTURE_FEATURES.md)
