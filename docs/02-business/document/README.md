# Document

> Company and employee files, policies, templates, contracts, and certificates.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Documents
>
> Storage architecture: [FILE_STORAGE.md](../../01-architecture/FILE_STORAGE.md)

---

## Purpose

Own document metadata, categorization, and authorized access to files. Binary content lives in file storage; MySQL stores metadata and links to company/employee (and other subjects).

---

## Responsibilities

| Area | Description |
|------|-------------|
| Company Files | Organization-level documents |
| Employee Files | Files attached to an employee |
| Policies | Policy documents |
| Templates | Reusable templates |
| Contracts | Contract documents (often employee-linked) |
| Certificates | Certificate documents |

---

## Business Rules

1. Metadata in DB; bytes in storage disk/object store.
2. Every download/upload/delete is permission-checked — public guessable URLs are not an access model.
3. Documents are company-scoped; no cross-company leakage.
4. Employee module may show document sections, but Documents (or a shared attachment service owned here) remains the authority for file records.
5. Recruitment/Onboarding/Assets should reuse Documents capabilities instead of parallel upload stacks when the artifact is a managed document.
6. Deletion follows retention rules (especially contracts/payslip PDFs/certificates).
7. Categories (policy/template/contract/certificate/…) are first-class enough for filtering and permissions.

---

## Key Workflows

### Upload

```
Authorized actor uploads file + category + owner (company/employee/…)
  → Validate mime/size → Store object → Save metadata → Audit if sensitive
```

### Download

```
Authorized request
  → Policy check → Stream or short-lived signed URL
```

### Replace / delete

```
Authorize → Store new object / mark deleted
  → Update metadata → Orphan cleanup job as needed
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Employee / Organization identity references | Payroll calculation |
| Authorization | Owning attendance/leave domain data |
| File storage service | |
| Notifications (optional share alerts) | |

Consumers: Employee UI, Recruitment, Onboarding, Assets, Payroll (payslip PDFs), Reports (exports).

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_company_documents` | View company files |
| `can_manage_company_documents` | Manage company files |
| `can_view_employee_documents` | View employee files (scoped) |
| `can_manage_employee_documents` | Manage employee files |
| `can_upload_own_documents` | Self-service upload when enabled |
| `can_manage_document_templates` | Templates/policies admin |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `DocumentUploaded` | Optional notify; audit for sensitive categories |
| `DocumentDeleted` | Audit; storage cleanup |
| `DocumentShared` | Notify recipients |

---

## Out of Scope / Future

- E-signature workflows
- Full DMS/versioning product parity
- OCR/AI document classification

---

## Related Documents

- [../employee/](../employee/)
- [../onboarding/](../onboarding/)
- [../asset/](../asset/)
- [../../01-architecture/FILE_STORAGE.md](../../01-architecture/FILE_STORAGE.md)
- `docs/06-api/DOCUMENT_API.md`
