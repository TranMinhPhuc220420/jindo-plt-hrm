# Onboarding

> Checklist-driven joining process from accepted offer to active/probation employee.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Onboarding

---

## Purpose

Turn an accepted hire into a ready employee: accounts, equipment, orientation, training, probation tracking, and completion. Onboarding sits between Recruitment and Employee in the lifecycle.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Checklist | Required joining tasks and completion state |
| Account Creation | User account provisioning for the new joiner |
| Equipment Assignment | Trigger/track equipment via Assets |
| Orientation | Orientation activities |
| Training | Initial training tasks |
| Probation | Probation period tracking |
| Completion | Mark onboarding done / hand off to steady-state Employee |

---

## Business Rules

1. Onboarding is typically created from Recruitment after offer acceptance (manual HR start allowed in foundation phases if needed).
2. Completion should result in a consistent Employee status (e.g. probation/active) — Employee module remains master of the employee record.
3. Account creation uses Authentication/user provisioning; Onboarding orchestrates, Auth owns credentials.
4. Equipment assignment goes through Assets services — Onboarding does not invent a parallel asset ledger.
5. Documents required at join use Documents module.
6. Checklist items can be mandatory vs optional; mandatory items block completion.
7. Probation end may later feed Performance/HR status flows; do not silently promote without policy.

---

## Key Workflows

### Start onboarding

```
OfferAccepted (or HR start)
  → Create onboarding case + checklist template
    → Create/link employee draft or employee record per policy
      → Assign tasks (HR, IT, manager, employee)
```

### Execute checklist

```
Task owners complete items
  → Account created → Equipment assigned → Documents uploaded → Training done
    → Progress updates → Notifications/reminders
```

### Complete onboarding

```
All mandatory items done
  → Verify → Mark completed
    → Employee status set for steady state
      → Audit → Notify
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Recruitment (trigger/context) | Payroll calculation |
| Employee (create/activate profile) | Attendance ownership |
| Auth (account creation) | Report writers for completion |
| Assets (equipment) | |
| Documents (join docs) | |
| Notifications (reminders) | |

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_onboarding` | View onboarding cases |
| `can_manage_onboarding` | Create/configure onboarding |
| `can_complete_onboarding_task` | Complete assigned tasks |
| `can_complete_onboarding` | Mark case completed |
| `can_manage_onboarding_templates` | Checklist templates |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `OnboardingStarted` | Notify stakeholders; create tasks |
| `OnboardingTaskCompleted` | Update progress; maybe unlock dependent tasks |
| `OnboardingCompleted` | Audit; notify; employee steady-state confirmation |
| `ProbationCheckpointReached` | Reminder to HR/manager |

---

## Out of Scope / Future

- Fully automated IT provisioning integrations
- E-signature for join documents

---

## Related Documents

- [../recruitment/](../recruitment/)
- [../employee/](../employee/)
- [../asset/](../asset/)
- [../document/](../document/)
- `docs/06-api/ONBOARDING_API.md`
