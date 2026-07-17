# Glossary

> Shared terminology for the HRM platform.
>
> Source of truth: [PROJECT_LOGIC.md](./PROJECT_LOGIC.md)

Use these terms consistently in docs, APIs, database names, and UI copy.

---

## Organization

| Term | Meaning |
|------|---------|
| Company | Top organizational boundary. Owns departments, employees, payroll, attendance, assets, and reports. |
| Branch | Subdivision of a company (location or legal/operational unit). |
| Department | Functional unit under a branch/company. |
| Team | Group within a department. |
| Position | Job title / role definition in the org structure (not the same as auth Role). |
| Employee | Person employed by the company and tracked through the lifecycle. |
| Manager | Managerial relationship for an employee. |
| Direct Supervisor | Immediate reporting supervisor. |
| HR Owner | HR contact responsible for the employee record. |
| Department Head | Head of the employee’s department. |

---

## Lifecycle

| Term | Meaning |
|------|---------|
| Candidate | Person in recruitment who is not yet an employee. |
| Recruitment | Process of sourcing, interviewing, evaluating, offering, and hiring. |
| Offer | Formal employment offer to a candidate. |
| Onboarding | Process after acceptance: checklist, account, equipment, orientation, training, probation. |
| Probation | Trial period after joining, tracked in onboarding. |
| Promotion | Career level/position advancement. |
| Transfer | Move across branch/department/team/position. |
| Resignation | Employee-initiated exit trigger. |
| Exit Process | Offboarding workflow before archival. |
| Archived Employee | Former employee retained for history/audit, no longer active. |

---

## Access Control

| Term | Meaning |
|------|---------|
| Authentication | Verifying identity (login, logout, password reset, 2FA, session). |
| Authorization | Deciding what an authenticated user may do or see. |
| Role | Named set of permissions assigned to users (e.g. HR, Manager, Employee). |
| Permission | Discrete capability string (e.g. `can_create_employee`, `can_approve_leave`). |
| Policy | Server-side rule that evaluates authorization for a resource/action. |
| Feature Visibility | Whether a feature is shown based on permissions. |
| Menu Visibility | Whether a navigation item is shown based on permissions. |

---

## Time & Attendance

| Term | Meaning |
|------|---------|
| Attendance | Record of presence, working time, and related exceptions. |
| Check-in / Check-out | Start and end markers of a work session. |
| Working Hours | Calculated time worked. |
| Late | Arrival after scheduled start. |
| Early Leave | Departure before scheduled end. |
| Overtime | Work beyond scheduled hours (also used in payroll calculation). |
| Break Time | Recorded break period. |
| Attendance Correction | Request/adjustment to fix attendance data. |
| Attendance Approval | Manager/HR confirmation of attendance or correction. |
| Holiday | Non-working calendar day defined by company rules. |
| Weekend Rules | Company rules for weekend work/rest. |

---

## Leave

| Term | Meaning |
|------|---------|
| Leave Request | Employee request for time off. |
| Leave Type | Category of leave (annual, sick, unpaid, etc.). |
| Leave Balance | Remaining entitlement for a leave type. |
| Leave Approval | Manager/HR decision on a leave request. |
| Compensation Leave | Leave granted in exchange for overtime/extra work. |
| Half-day Leave | Leave measured as half of a working day. |
| Hourly Leave | Leave measured in hours. |

---

## Shift

| Term | Meaning |
|------|---------|
| Shift Definition | Named schedule template (start/end, rules). |
| Shift Assignment | Mapping of shifts to employees/calendars. |
| Working Calendar | Calendar of planned work days/shifts. |
| Rotating Shift | Pattern that cycles across shift definitions. |
| Night Shift | Shift spanning night hours. |
| Flexible Shift | Shift with flexible start/end constraints. |
| Overtime Rule | Rule set defining when overtime applies. |

---

## Payroll

| Term | Meaning |
|------|---------|
| Salary | Base pay component. |
| Allowance | Additional pay component. |
| Bonus | Discretionary or rule-based extra pay. |
| Deduction | Amount subtracted from pay. |
| Tax | Tax calculation/withholding related to payroll. |
| Insurance | Insurance contributions related to payroll. |
| Payroll Calculation | Process that computes net/gross pay from inputs. |
| Payroll Approval | Authorization step before finalizing a payroll run. |
| Payslip | Employee-facing statement of a pay period. |
| Payroll History | Historical record of payroll runs and payslips. |

---

## Recruitment & Onboarding

| Term | Meaning |
|------|---------|
| Job Position | Open or defined hiring position in recruitment. |
| Interview | Structured evaluation step for a candidate. |
| Evaluation | Scoring/feedback from interviews or assessments. |
| Hiring | Conversion of accepted candidate into onboarding/employee. |
| Onboarding Checklist | Task list required to complete joining. |
| Equipment Assignment | Issuing company assets during onboarding (links to Assets). |

---

## Performance

| Term | Meaning |
|------|---------|
| Goal | Target outcome for an employee/team. |
| KPI | Key Performance Indicator metric. |
| OKR | Objectives and Key Results framework. |
| Evaluation | Formal performance assessment. |
| Review Cycle | Time-boxed performance review period. |
| Promotion Suggestion | System/manager suggestion based on performance outcomes. |

---

## Assets & Documents

| Term | Meaning |
|------|---------|
| Asset | Company-owned item that can be assigned to an employee. |
| Asset Inventory | Catalog and status of assets. |
| Maintenance | Upkeep activity for an asset. |
| Damage Report | Record of asset damage. |
| Company Files | Organization-level documents. |
| Employee Files | Documents attached to an employee record. |
| Policy / Template / Contract / Certificate | Common document categories managed by the Documents module. |

---

## Notifications & Reports

| Term | Meaning |
|------|---------|
| System Notification | In-app notification. |
| Push Notification | Push channel notification (future/mobile-ready). |
| Reminder | Time-triggered notification for an upcoming action/event. |
| Scheduled Notification | Notification queued to send at a defined time. |
| Report | Aggregated view of domain data (attendance, payroll, leave, etc.). |
| Custom Report | User/configurable report beyond standard templates. |
| Dashboard | Aggregated operational view built on reports and domain data. |

---

## Technical

| Term | Meaning |
|------|---------|
| Module | Bounded feature area with clear ownership and dependencies. |
| Service | Layer that owns business rules and orchestrates use cases. |
| Repository | Persistence abstraction over the database. |
| Controller | Thin API/presentation entry point; must not own business rules. |
| Form Request | Backend validation object for incoming input. |
| REST API | HTTP resource-oriented API style used by the platform. |
| Audit Log | Trace record of important actions for accountability. |
| Multi-company Ready | Design assumption that company is a first-class scope, even if v1 runs one company. |

---

## Related Documents

- [PROJECT_LOGIC.md](./PROJECT_LOGIC.md)
- [BUSINESS_SCOPE.md](./BUSINESS_SCOPE.md)
- [DEVELOPMENT_PRINCIPLES.md](./DEVELOPMENT_PRINCIPLES.md)
