# Performance

> Goals, KPI/OKR, evaluations, review cycles, and promotion suggestions.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Performance

---

## Purpose

Run structured performance management for employees. Outputs may inform promotion suggestions; they do not silently change Employee position or Payroll without HR workflows.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Goals | Employee/team targets |
| KPI | Key performance indicators |
| OKR | Objectives and key results |
| Evaluation | Formal assessments |
| Review Cycle | Time-boxed review periods |
| Promotion Suggestion | Suggested advancement signals |

---

## Business Rules

1. Performance records reference employees and are company-scoped.
2. Review cycles define who is evaluated, when, and by whom.
3. Evaluations require authorization; employees may view own results per policy.
4. Promotion suggestion is advisory — applying a promotion is an Employee/HR action (audited), not an automatic side effect unless explicitly designed later.
5. Goals/KPI/OKR can coexist; do not force one framework exclusively in the data model if the product supports multiple.
6. Managers typically evaluate direct reports; policies enforce relationship scope.

---

## Key Workflows

### Run a review cycle

```
HR opens Review Cycle
  → Assign participants + forms/framework
    → Employees set goals (optional phase)
      → Managers evaluate
        → Calibration/HR review (if configured)
          → Finalize results → Notify → Archive in cycle history
```

### Promotion suggestion

```
Evaluation outcomes / rules
  → Generate suggestion
    → HR reviews → May start Employee promotion workflow
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Employee | Payroll auto-raises without HR process |
| Organization (manager relationships) | Attendance ownership |
| Notifications | Circular dependency with Employee writes beyond explicit handoff |
| Reports (consumes performance data) | |

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_performance` | View performance data (scoped) |
| `can_manage_goals` | Create/update goals |
| `can_evaluate_employee` | Submit evaluations |
| `can_manage_review_cycles` | Configure/run cycles |
| `can_view_promotion_suggestions` | See suggestions |
| `can_manage_performance_settings` | Framework configuration |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `ReviewCycleStarted` | Notify participants |
| `EvaluationSubmitted` | Notify HR/manager as configured |
| `ReviewCycleFinalized` | Audit; notify; unlock suggestions |
| `PromotionSuggested` | Notify HR |

---

## Out of Scope / Future

- AI performance analysis
- Fully automated promotion/payroll linkage

---

## Related Documents

- [../employee/](../employee/)
- [../report/](../report/)
- `docs/06-api/PERFORMANCE_API.md`
