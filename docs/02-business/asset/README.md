# Asset

> Company asset inventory, assignment, return, maintenance, damage, and replacement.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Asset Management

---

## Purpose

Track company-owned assets and their custody by employees. Assignment/return is auditable and integrates with onboarding/exit flows through services.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Asset Inventory | Catalog of assets and statuses |
| Assign Asset | Give custody to an employee |
| Return Asset | Return to company inventory |
| Maintenance | Maintenance records/schedule |
| Damage Report | Damage incidents and evidence |
| Replacement | Replace damaged/lost/retired assets |

---

## Business Rules

1. Assets are company-scoped inventory items with a clear status (available, assigned, maintenance, retired, lost, etc.).
2. Assigning an asset creates an auditable custody record (`Asset assigned`).
3. An asset should not be assigned to two employees at once unless explicitly supporting shared custody (default: single assignee).
4. Onboarding may request assignment through Assets services; Assets owns inventory truth.
5. Exit process should require return (or mark exception) before clean archival when policy demands.
6. Damage reports may attach files via file storage/Documents patterns.
7. Replacement links old and new asset records rather than erasing history.

---

## Key Workflows

### Assign

```
Authorized actor selects available asset + employee
  → Validate status → Assign → Audit → Notify employee
```

### Return

```
Return request/handshake
  → Inspect condition → Update status → Close assignment → Audit
```

### Damage → replacement

```
Damage report filed
  → Review → Maintenance or Replacement
    → Retire/replace asset → Optional new assignment
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Employee | Payroll |
| Documents/file storage (evidence) | Attendance |
| Onboarding/Exit orchestration calling Assets | Being bypassed by Onboarding writing inventory tables directly |
| Notifications | |

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_assets` | View inventory |
| `can_manage_assets` | Create/update inventory |
| `can_assign_asset` | Assign assets |
| `can_return_asset` | Process returns |
| `can_report_asset_damage` | File damage reports |
| `can_manage_asset_maintenance` | Maintenance operations |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `AssetAssigned` | Audit (required); notify employee |
| `AssetReturned` | Audit; notify |
| `AssetDamageReported` | Notify admin/HR |
| `AssetReplaced` | Audit; inventory updated |

---

## Out of Scope / Future

- Full CMMS / facilities management
- IoT/tracking device integrations

---

## Related Documents

- [../onboarding/](../onboarding/)
- [../employee/](../employee/)
- [../document/](../document/)
- [../../01-architecture/FILE_STORAGE.md](../../01-architecture/FILE_STORAGE.md)
- `docs/06-api/ASSET_API.md`
