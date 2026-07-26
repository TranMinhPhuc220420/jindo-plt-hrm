# File Storage

> Architecture for storing and serving company/employee files and related binaries.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) §6 Documents / Assets / Onboarding

---

## Purpose

Define how binary files are stored, authorized, and linked to domain records without putting file bytes into MySQL or bypassing permission checks.

---

## What Is Stored as Files

| Category | Examples |
|----------|----------|
| Employee files | Contracts, certificates, ID scans, avatars |
| Company files | Policies, templates, handbooks |
| Recruitment | Resumes, offer letters |
| Onboarding / Assets | Signed forms, equipment photos, damage evidence |
| Attendance | Check-in/out camera photos (private disk; mediated download) |
| Payroll (optional) | Generated payslip PDFs if stored as files |
| Exports | Report downloads (often temporary) |

Structured metadata stays in the database; binaries stay in the storage disk/object store.

---

## Architecture Split

```
Client upload/download
  → API (auth + permission + validation)
    → Documents / owning module service
      → Storage driver (local / S3-compatible / etc.)
      → DB metadata row (owner, path/key, mime, size, checksum, visibility)
```

| In database | In file storage |
|-------------|-----------------|
| Owner type/id, company scope, category, permissions context | Object bytes |
| Original filename, mime, size, checksum | Optional thumbnails/derivatives |
| Storage disk + object key | Temporary signed URL targets |

---

## Ownership & Modules

- **Documents module** is the primary owner of company/employee document metadata and access patterns.
- Other modules may attach files through Documents services or clearly owned attachment services — they must not invent parallel unauthorized upload endpoints.
- **Attendance** stores punch photos on the private `local` disk and serves them only via authenticated download (`GET /api/attendance/records/{id}/evidences/{punchType}/photo`).
- **Assets** may store images/evidence related to inventory and damage reports.
- **Onboarding** may require document/equipment evidence but should reuse Documents/Assets capabilities.

---

## Authorization Rules

1. Every upload/download/delete goes through authentication + permission/policy checks.
2. Employee files are not public URLs without signed/temporary access.
3. Company policies may be broader, but still permission-gated for management actions.
4. “Knows the URL” is not authorization — prefer mediated download endpoints or short-lived signed URLs.
5. Multi-company readiness: object keys and metadata must not leak across companies.

Example permissions (illustrative):

- `can_view_employee_documents`
- `can_manage_company_documents`
- `can_upload_own_documents` (if self-service is enabled)

---

## Validation & Safety

- Validate mime type / extension / max size at the API boundary.
- Store a generated object key; do not trust client paths.
- Prefer virus-scanning hooks later if required by deployment policy.
- Strip or ignore executable content types where not needed.

---

## Lifecycle

```
Upload → Validate → Store object → Save metadata → (optional) event/audit
Download → Authorize → Stream or signed URL
Replace → Authorize → Store new object → Update metadata → Delete/orphan old object
Delete → Authorize → Soft-delete metadata and/or remove object per retention policy
```

Orphan cleanup should be a planned job when replacements/deletes leave unused objects.

---

## Retention & Exit Process

When an employee exits/archives:

- Keep files required for legal/HR history according to company policy.
- Revoke active access for self-service where appropriate.
- Do not hard-delete historical contracts/payslips without an explicit retention rule.

---

## Implementation Notes (non-prescriptive)

- Laravel filesystem disks are an acceptable abstraction.
- Local disk is fine for early development; object storage is preferred for production scale.
- Keep the storage interface behind a service so disk changes do not ripple through controllers.

---

## Related Documents

- [DATABASE_ARCHITECTURE.md](./DATABASE_ARCHITECTURE.md)
- [API_ARCHITECTURE.md](./API_ARCHITECTURE.md)
- [AUTHORIZATION.md](./AUTHORIZATION.md)
- [EVENT_FLOW.md](./EVENT_FLOW.md)
- `docs/02-business/document/` (when filled)
- `docs/02-business/asset/` (when filled)
