# Permissions Catalog

> Canonical `can_*` keys for the HRM platform.
>
> Rules: [AUTHORIZATION.md](./AUTHORIZATION.md)  
> Admin API: [ROLES_API.md](../06-api/ROLES_API.md)

---

## Rules

1. **Permission-first** — never authorize by role display name.
2. Keys are stable `can_*` snake_case strings; do not invent synonyms in controllers.
3. Domain modules own the meaning; Authorization stores/assigns them.
4. New keys require updating this catalog **and** the module’s business/API docs.
5. Scope (own / team / company) is enforced in policies, not encoded in the key name unless the capability is inherently self-only (e.g. `can_view_own_profile`).

---

## Foundation

| Permission | Intent |
|------------|--------|
| `can_view_organization` | View org tree |
| `can_manage_organization` | CRUD branches/departments/teams/positions |
| `can_manage_company` | Update company profile |
| `can_view_roles` | List roles/permissions |
| `can_manage_roles` | CRUD roles + permission sets |
| `can_assign_roles` | Attach roles to users |
| `can_view_settings` | View settings |
| `can_manage_settings` | Update settings |
| `can_view_audit_logs` | Browse audit trail |

---

## Employee

| Permission | Intent |
|------------|--------|
| `can_view_employee` | List/show employees (scoped) |
| `can_create_employee` | Create employee |
| `can_update_employee` | Update non-sensitive fields |
| `can_manage_employee_sensitive` | Bank/tax/insurance fields |
| `can_change_employee_status` | Status / archive |
| `can_view_own_profile` | Self profile (limited) |

---

## Attendance

| Permission | Intent |
|------------|--------|
| `can_check_in_out` | Own punches |
| `can_view_attendance` | Lists/summaries (scoped) |
| `can_request_attendance_correction` | Request corrections |
| `can_approve_attendance` | Approve records/corrections |
| `can_manage_attendance` | HR overrides |

---

## Leave

| Permission | Intent |
|------------|--------|
| `can_request_leave` | Create/cancel own requests |
| `can_view_leave` | View leave (scoped) |
| `can_approve_leave` | Approve/reject |
| `can_manage_leave_types` | CRUD leave types |
| `can_manage_leave_balances` | Manual balance adjust |
| `can_manage_holidays` | Holidays / weekend rules |

---

## Shift

| Permission | Intent |
|------------|--------|
| `can_view_shifts` | Read definitions/assignments |
| `can_view_own_schedule` | Own calendar |
| `can_manage_shift_definitions` | CRUD shifts |
| `can_assign_shifts` | Assignments |
| `can_manage_overtime_rules` | Overtime rules |

---

## Payroll

| Permission | Intent |
|------------|--------|
| `can_view_salary` | Own/scoped salary & payslip |
| `can_manage_salary` | Edit compensation components |
| `can_run_payroll` | Create/calculate runs |
| `can_approve_payroll` | Approve runs |
| `can_view_payroll_history` | Historical runs |
| `can_manage_payslips` | Admin payslip ops |

---

## Recruitment

| Permission | Intent |
|------------|--------|
| `can_manage_job_positions` | Job openings |
| `can_view_candidates` | View pipeline |
| `can_manage_candidates` | CRUD candidates |
| `can_manage_interviews` | Interviews |
| `can_create_offer` | Create offers |
| `can_approve_offer` | Approve offers (if dual control) |
| `can_hire_candidate` | Handoff to onboarding |

---

## Onboarding

| Permission | Intent |
|------------|--------|
| `can_view_onboarding` | View cases |
| `can_manage_onboarding` | Create/configure |
| `can_complete_onboarding_task` | Complete assigned tasks |
| `can_complete_onboarding` | Mark case completed |
| `can_manage_onboarding_templates` | Templates |

---

## Performance

| Permission | Intent |
|------------|--------|
| `can_view_performance` | View (scoped) |
| `can_manage_goals` | Goals |
| `can_evaluate_employee` | Submit evaluations |
| `can_manage_review_cycles` | Cycles |
| `can_view_promotion_suggestions` | Suggestions |
| `can_manage_performance_settings` | Framework config |

---

## Assets

| Permission | Intent |
|------------|--------|
| `can_view_assets` | Inventory |
| `can_manage_assets` | CRUD assets |
| `can_assign_asset` | Assign |
| `can_return_asset` | Return |
| `can_report_asset_damage` | Damage reports |
| `can_manage_asset_maintenance` | Maintenance |

---

## Documents

| Permission | Intent |
|------------|--------|
| `can_view_company_documents` | Company files |
| `can_manage_company_documents` | Manage company files |
| `can_view_employee_documents` | Employee files (scoped) |
| `can_manage_employee_documents` | Manage employee files |
| `can_upload_own_documents` | Self upload |
| `can_manage_document_templates` | Templates/policies admin |

---

## Notifications

| Permission | Intent |
|------------|--------|
| `can_view_own_notifications` | Inbox |
| `can_manage_notification_templates` | Admin templates |
| `can_send_broadcast_notification` | Broadcasts |
| `can_manage_notification_settings` | Defaults |

---

## Reports

| Permission | Intent |
|------------|--------|
| `can_view_attendance_reports` | Attendance reports |
| `can_view_payroll_reports` | Payroll reports (sensitive) |
| `can_view_leave_reports` | Leave reports |
| `can_view_employee_reports` | Employee/headcount |
| `can_view_performance_reports` | Performance reports |
| `can_manage_custom_reports` | Custom definitions |
| `can_export_reports` | Exports |

---

## Seeding (required)

[SEEDING.md](../03-database/SEEDING.md) must seed **all keys in this catalog** for modules included in the release. This file is the source of truth for key strings; seeders must not invent synonyms.

Phase 01 foundation minimum: Organization + Roles + Settings + Audit sections above.

Typical role bundles (exact sets live in `RoleSeeder`):

| Role | Typical grants |
|------|----------------|
| Admin | Broad manage_* + roles/settings/audit |
| HR | Employee, leave/attendance manage, payroll ops (not always approve) |
| Manager | View team + approve leave/attendance + view performance |
| Employee | Own profile, check-in, request leave, own salary/payslip, own notifications |

Roles admin UI/API: [AUTHORIZATION.md](./AUTHORIZATION.md) § Roles administration · [ROLES_API.md](../06-api/ROLES_API.md).

---

## Related

- [AUTHORIZATION.md](./AUTHORIZATION.md)
- [../06-api/ROLES_API.md](../06-api/ROLES_API.md)
- [../03-database/SEEDING.md](../03-database/SEEDING.md)
- [../05-frontend/ROUTING.md](../05-frontend/ROUTING.md)
- [../02-business/](../02-business/)
