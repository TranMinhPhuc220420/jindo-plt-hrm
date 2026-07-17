# Audit API

> Read audit trail (append-only writes happen inside domain services/listeners).
>
> Business: [../02-business/audit/README.md](../02-business/audit/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Path

```
/api/audit-logs
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_audit_logs` | List/show |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/audit-logs` | Filtered list |
| `GET` | `/api/audit-logs/{id}` | Detail |

No public create/update/delete — writes are internal.

---

## List

`GET /api/audit-logs?actor_id=&action=&subject_type=&subject_id=&date_from=&date_to=&page=`

```json
{
  "success": true,
  "data": [
    {
      "id": 1001,
      "action": "leave.rejected",
      "actor_id": 2,
      "subject_type": "leave_request",
      "subject_id": 44,
      "company_id": 1,
      "created_at": "2026-07-16T10:00:00Z",
      "payload": { "reason": "Coverage" }
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 1, "last_page": 1 }
}
```

---

## Related

- [../01-architecture/EVENT_FLOW.md](../01-architecture/EVENT_FLOW.md)
- [../04-backend/EVENTS.md](../04-backend/EVENTS.md)
