# Document API

> Upload, metadata, download, and delete for company/employee files.
>
> Business: [../02-business/document/README.md](../02-business/document/README.md)  
> Storage: [FILE_STORAGE.md](../01-architecture/FILE_STORAGE.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Path

```
/api/documents
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_company_documents` | Company files |
| `can_manage_company_documents` | Manage company files |
| `can_view_employee_documents` | Employee files (scoped) |
| `can_manage_employee_documents` | Manage employee files |
| `can_upload_own_documents` | Self upload |
| `can_manage_document_templates` | Templates/policies admin |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/documents` | List (`owner_type`, `owner_id`, `category`) |
| `POST` | `/api/documents` | Upload (`multipart/form-data`) |
| `GET` | `/api/documents/{id}` | Metadata |
| `GET` | `/api/documents/{id}/download` | Authorized download |
| `PATCH` | `/api/documents/{id}` | Update metadata |
| `DELETE` | `/api/documents/{id}` | Soft-delete / remove per policy |

---

## Upload

`POST /api/documents` (`multipart/form-data`)

| Field | Type | Notes |
|-------|------|-------|
| `file` | file | Required |
| `category` | string | `policy` \| `template` \| `contract` \| `certificate` \| `other` \| … |
| `owner_type` | string | `company` \| `employee` \| `candidate` \| … |
| `owner_id` | number | Required for non-company as modeled |
| `title` | string | Optional display title |

**201**

```json
{
  "success": true,
  "data": {
    "id": 55,
    "title": "Offer Letter.pdf",
    "category": "contract",
    "owner_type": "employee",
    "owner_id": 10,
    "mime_type": "application/pdf",
    "size": 204800,
    "created_at": "2026-07-16T10:00:00Z"
  }
}
```

Do not return permanent public storage URLs for private files.

---

## Download

`GET /api/documents/{id}/download`

- Stream file with authz check, **or**
- JSON with short-lived `download_url` in `data`

---

## List

`GET /api/documents?owner_type=employee&owner_id=10&category=contract&page=`

---

## Error Codes

| Code | When |
|------|------|
| `DOCUMENT_INVALID_TYPE` | Mime/extension rejected |
| `DOCUMENT_TOO_LARGE` | Over max size |
| `DOCUMENT_FORBIDDEN` | No permission |

---

## Related

- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
- [RECRUITMENT_API.md](./RECRUITMENT_API.md)
- [ONBOARDING_API.md](./ONBOARDING_API.md)
- [ASSET_API.md](./ASSET_API.md)
