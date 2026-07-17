# 09 — Roadmap

Delivery plan for the HRM platform, broken into implementable phases.

Source of truth for scope: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) §9  
Business boundaries: [BUSINESS_SCOPE.md](../00-overview/BUSINESS_SCOPE.md)

---

## Documents

| File | Purpose |
|------|---------|
| [MASTER_ROADMAP.md](./MASTER_ROADMAP.md) | Phase map, dependencies, exit criteria |
| [PHASE_01_FOUNDATION.md](./PHASE_01_FOUNDATION.md) | Auth, authz, org, settings, shell |
| [PHASE_02_EMPLOYEE.md](./PHASE_02_EMPLOYEE.md) | Employee master data |
| [PHASE_03_ATTENDANCE.md](./PHASE_03_ATTENDANCE.md) | Attendance |
| [PHASE_04_LEAVE.md](./PHASE_04_LEAVE.md) | Leave + holidays |
| [PHASE_05_SHIFT.md](./PHASE_05_SHIFT.md) | Shifts + calendars |
| [PHASE_06_PAYROLL.md](./PHASE_06_PAYROLL.md) | Payroll |
| [PHASE_07_RECRUITMENT.md](./PHASE_07_RECRUITMENT.md) | Recruitment, onboarding, docs, assets |
| [PHASE_08_PERFORMANCE.md](./PHASE_08_PERFORMANCE.md) | Performance, reports, notifications, audit |
| [FUTURE_FEATURES.md](./FUTURE_FEATURES.md) | Post-v1 / advanced (PROJECT_LOGIC Phase 6+) |

---

## How to Use

1. Read **MASTER_ROADMAP** for order and dependencies.
2. Implement one phase at a time; do not skip foundation.
3. Each phase links to business, API, DB, and UI docs.
4. Update phase status in MASTER when a phase completes.

---

## Mapping to PROJECT_LOGIC Phases

| Logic phase | Roadmap docs |
|-------------|--------------|
| Phase 1 Foundation | PHASE_01 + PHASE_02 |
| Phase 2 Time | PHASE_03 + PHASE_04 + PHASE_05 |
| Phase 3 Payroll | PHASE_06 |
| Phase 4 Hire & Ops | PHASE_07 |
| Phase 5 Insight | PHASE_08 |
| Phase 6 Advanced | FUTURE_FEATURES |

---

## Related

- [../08-development/RELEASE_PROCESS.md](../08-development/RELEASE_PROCESS.md)
- [../07-uiux/DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)
