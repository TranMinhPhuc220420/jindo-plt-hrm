# Dependency Rules

> Module and layer dependency rules for the HRM platform.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) §7–§8
>
> Principles: [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)

---

## Purpose

Prevent circular coupling and keep modules replaceable. Every dependency should flow **downward**.

---

## Golden Rules

1. **Dependencies flow downward only.**
2. **No circular dependency** between modules.
3. **Cross-module access uses services**, not foreign repositories/tables as a public API.
4. **Controllers never import another module’s internals** to “just query quickly”.
5. **UI never encodes server business ownership** between modules.
6. **Events must not be used to hide an illegal upward dependency.**

---

## Allowed Module Dependency Direction

```
Authentication
  → Authorization
    → Organization
      → Employee
        → Attendance
        → Leave
        → Shift
        → Payroll
        → Performance
        → Assets
        → Documents
          → Reports
            → Dashboard
```

Supporting modules:

| Module | May depend on | Must not depend on |
|--------|---------------|--------------------|
| Notifications | Identity of users/employees; event payloads | Owning domain write models as source of truth |
| Audit Logs | Actor + subject references | Domain modules depending on audit to complete core writes (audit is a side effect) |
| Settings / System | Low-level config | Business modules depending upward on UI settings screens |

---

## Critical Examples

### Attendance vs Payroll

- **Allowed:** Payroll services read attendance summaries through Attendance services during calculation.
- **Forbidden:** Attendance services create/update payslips or import Payroll repositories.
- **Forbidden:** Shared table owned by both modules for “convenience”.

### Leave vs Attendance / Shift

- Leave may consult shift/working calendar services for validation.
- Attendance may consult approved leave for working-hour interpretation.
- Neither should absorb the other’s write model.

### Recruitment → Onboarding → Employee

```
Recruitment (Offer Accepted)
  → Onboarding (checklist, account, equipment)
    → Employee (active employee record)
```

This is downward through the lifecycle and is encouraged.

### Reports / Dashboard

- Reports depend on domain modules (or read models fed by them).
- Domain modules must not call Report services to complete operational writes.

---

## Layer Dependency Rules

```
Presentation → API → Application Services → Domain Services → Repositories → Database
```

| From | May call | Must not call |
|------|----------|---------------|
| Controller | Application/Domain services, policies | Other module repositories, query builders for foreign domains |
| Domain service | Own repositories; other modules’ **services** downward/sideways as allowed | Controllers, UI, report writers for core path |
| Repository | DB / Eloquent models it owns | Other modules’ services (keep repos persistence-only) |
| Listener / Job | Services | Controllers |
| React feature | API client | Direct DB, PHP classes, inventing server rules |

---

## Replaceability Constraint

A dependency is too strong if replacing a module requires rewriting callers beyond an agreed service interface.

Design for known future variants:

| Module | Keep stable | Hide behind interface |
|--------|-------------|------------------------|
| Attendance | AttendanceService contract for summaries/check-in | Manual / GPS / biometric providers |
| Payroll | PayrollCalculationService contract | Monthly / hourly / commission strategies |

---

## Multi-company Constraint

- Higher modules may assume company context is available.
- Do not introduce reverse dependencies so that Organization must import Payroll to know it “has payroll enabled” for basic org CRUD — use settings/feature flags owned appropriately.

---

## Enforcement Checklist

Before adding an import/call across modules:

- [ ] Does the arrow point downward on the domain map?
- [ ] Is the call going through a service (not a foreign table)?
- [ ] Would swapping the callee’s implementation still compile against a stable contract?
- [ ] Are side effects done via events/listeners without creating a cycle?
- [ ] Is authorization still enforced in the owning module?

If any answer is no, redesign.

---

## Anti-Patterns

1. `Attendance` → `Payroll` write coupling
2. Shared “god” helper that all modules import for everything
3. Repository-to-repository calls across domains
4. Frontend composing forbidden workflows the API would reject if done step-by-step
5. Circular events: A listens to B and B listens to A to finish one use case

---

## Related Documents

- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
- [BACKEND_ARCHITECTURE.md](./BACKEND_ARCHITECTURE.md)
- [EVENT_FLOW.md](./EVENT_FLOW.md)
- [../00-overview/PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)
- [../00-overview/BUSINESS_SCOPE.md](../00-overview/BUSINESS_SCOPE.md)
