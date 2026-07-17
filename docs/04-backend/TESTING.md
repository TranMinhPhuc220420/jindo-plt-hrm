# Testing

> Backend testing strategy for HRM modules (Pest + Laravel).
>
> Principles: every module should be independently testable.

---

## Tooling

| Tool | Role |
|------|------|
| Pest | Primary test framework (project standard) |
| Laravel HTTP tests | Feature / API tests |
| Factories | Deterministic fake models |
| `Event::fake` / `Queue::fake` | Side-effect isolation |
| SQLite/MySQL test DB | Per CI/local convention |

---

## Test Pyramid for This Project

| Layer | What to cover | Speed |
|-------|---------------|-------|
| Unit | Services, policies, rules, pure calculators | Fast |
| Feature | API endpoints + authz + DB | Medium |
| Integration (selective) | Jobs consuming real queue drivers in staging | Slower |

Prefer many unit/feature tests over brittle end-to-end UI tests for backend rules.

---

## Directory Layout

```
tests/
  Unit/
    Services/
      Leave/
      Payroll/
    Policies/
    Rules/
  Feature/
    Employee/
    Attendance/
    Leave/
    Payroll/
    ...
```

Mirror module names from `docs/02-business/`.

---

## What Must Be Tested

### Per feature (minimum)

- [ ] Happy path API success
- [ ] Validation 422 cases (critical fields)
- [ ] Forbidden 403 (missing permission / wrong relationship)
- [ ] Unauthenticated 401
- [ ] Company scope isolation (cannot read/write other company)
- [ ] Important domain invariant (balance, finalize immutability, etc.)

### Cross-cutting

- [ ] Permission-first policies (no role-name shortcuts)
- [ ] Event dispatched on important outcomes
- [ ] Audit side effect for required actions (directly or via faked listener expectations)
- [ ] Attendance does not write payroll; payroll consumes summaries safely

---

## Service Tests

Unit-test services when rules are non-trivial:

- Leave approval/rejection + balance effects
- Attendance late/overtime interpretation inputs
- Payroll calculation strategy (monthly v1)
- Employee status transitions

Fake repositories/collaborators or use DB with factories — pick the style that keeps the test clear.

---

## Policy Tests

Cover matrices:

| Actor | Resource | Ability | Expected |
|-------|----------|---------|----------|
| Employee | own leave | view | allow |
| Employee | other’s leave | view | deny |
| Manager | report’s leave | approve | allow |
| Manager | stranger leave | approve | deny |
| HR with permission | employee create | create | allow |

---

## Feature / API Tests

Example patterns:

```
postJson /api/leave-requests → 201
postJson approve → 200 + status approved
postJson approve as outsider → 403
postJson with invalid dates → 422
```

Assert against [API_RESPONSE.md](./API_RESPONSE.md) envelope keys where helpful.

---

## Events, Listeners, Queues

| Technique | Use |
|-----------|-----|
| `Event::fake()` | Assert domain events from services/HTTP |
| `Queue::fake()` | Assert jobs pushed |
| Listener unit tests | Handoff logic (OfferAccepted → onboarding) |

Do not require real SMTP in CI.

---

## Factories & Seeders

- Factories for tests; seeders for human demo datasets
- Factories must set `company_id` on company-scoped models
- Avoid depending on full `DatabaseSeeder` in unit tests

See [SEEDING.md](../03-database/SEEDING.md).

---

## Multi-company Readiness Tests

Even with one company in v1 demos, add tests that:

- Create two companies
- Ensure queries/policies cannot cross-read employees, leaves, payslips

---

## Naming Tests

Use descriptive Pest names:

```
it('allows a manager to approve leave for a direct report')
it('rejects payroll finalize when run is already finalized')
it('prevents attendance module from creating payslips') // architectural guard if enforceable
```

---

## CI Expectations

- Tests run on fresh migrated DB
- Pint/static analysis may run separately
- Flaky tests are treated as failures to fix, not ignore

---

## Anti-Patterns

1. Testing only controllers with everything mocked away (proves nothing)
2. One 2000-line feature test file for all modules
3. Relying on production dumps
4. Asserting on full HTML/Inertia pages for pure API domain rules
5. Skipping authz tests “because UI hides the button”

---

## Related Documents

- [SERVICES.md](./SERVICES.md)
- [POLICIES.md](./POLICIES.md)
- [EVENTS.md](./EVENTS.md)
- [../01-architecture/DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md)
- [../08-development/REVIEW_CHECKLIST.md](../08-development/REVIEW_CHECKLIST.md)
