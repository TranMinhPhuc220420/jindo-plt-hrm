# Employee

> Core people module — the backbone record every operational module revolves around.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §4–§6

---

## Purpose

Own the employee profile and employment identity after hiring/onboarding. Other modules reference employees; they must not redefine the employee master data.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Employee Profile | Identity, personal data, org placement (branch/department/team/position) |
| Emergency Contact | Contacts for emergencies |
| Education | Education history |
| Work History | Prior experience / internal history snapshots as modeled |
| Family | Dependent/family information where required |
| Documents | Links to employee files (storage owned with Documents module) |
| Contracts | Employment contracts metadata and lifecycle links |
| Bank Information | Payroll disbursement details |
| Insurance | Insurance-related employee data |
| Tax Information | Tax identifiers and related fields |
| Status | Active, probation, suspended, resigned, archived, etc. |

Organization relationships that may attach to an employee:

- Manager
- Direct Supervisor
- HR Owner
- Department Head

---

## Position in Lifecycle

```
… → Onboarding → Employee → Attendance / Leave / Payroll / Performance → …
                              → Promotion / Transfer → Resignation → Exit → Archived
```

Employee is created/activated from onboarding (or controlled HR create flows in foundation phases). Archival happens through exit process — not silent deletes.

---

## Business Rules

1. Every employee belongs to a **company** (multi-company ready).
2. Org placement follows Company → Branch → Department → Team → Position.
3. Status transitions must be explicit and auditable (especially salary-impacting or exit-related changes).
4. Sensitive fields (bank, tax, salary-linked data) require stricter permissions.
5. Employee code / identifiers should be unique per company.
6. Soft references from other modules use `employee_id`; they do not copy master profile fields as a second source of truth.
7. Editing an employee is an auditable action.

---

## Key Workflows

### Create / activate employee

```
HR (or Onboarding completion)
  → Validate org placement + required profile fields
    → Create employee (status: probation/active as policy)
      → Link user account if applicable
        → Audit + notify
```

### Update profile

```
Authorized actor
  → Authorize field group (profile vs bank vs tax, etc.)
    → Validate → Persist → Audit (Employee edited)
```

### Transfer / promotion (status & org change)

```
Authorized HR/Manager flow
  → Update position/department/manager as allowed
    → Audit → Notify relevant parties
```

### Exit / archive

```
Resignation / termination trigger
  → Exit process (revoke access, return assets, final payroll handoff via other modules)
    → Status → archived
      → Retain history for reports/audit
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Organization (company/branch/dept/team/position) | Payroll calculation internals |
| Authorization (policies/permissions) | Report writers to complete profile writes |
| Documents service for file attachments | Circular ownership with Attendance/Leave |

Downstream consumers: Attendance, Leave, Shift, Payroll, Performance, Assets, Documents, Reports.

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_employee` | View employee profiles (scope via policy: self/team/company) |
| `can_create_employee` | Create employee records |
| `can_update_employee` | Update non-sensitive profile fields |
| `can_manage_employee_sensitive` | Bank/tax/insurance fields |
| `can_change_employee_status` | Status transitions including archive |
| `can_view_own_profile` | Employee self-service |

Never authorize by role name strings in services.

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `EmployeeCreated` | Audit; notify HR owner; optional welcome notification |
| `EmployeeUpdated` | Audit |
| `EmployeeStatusChanged` | Audit; notify; trigger exit-related listeners when resigning/archived |

---

## Out of Scope / Future

- Full public employee self-service portal (later)
- Deep ESS mobile experiences (later)
- Treating candidate records as employees before hire (belongs to Recruitment)

---

## Related Documents

- [../onboarding/](../onboarding/)
- [../recruitment/](../recruitment/)
- [../document/](../document/)
- [../../01-architecture/DEPENDENCY_RULES.md](../../01-architecture/DEPENDENCY_RULES.md)
- `docs/06-api/EMPLOYEE_API.md`
