# Notification API

> In-app inbox, read state, preferences. Email/push are delivery channels driven by events.
>
> Business: [../02-business/notification/README.md](../02-business/notification/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/notifications
/api/notification-preferences
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_own_notifications` | Inbox |
| `can_manage_notification_templates` | Admin templates (separate admin API if needed) |
| `can_send_broadcast_notification` | Broadcasts |
| `can_manage_notification_settings` | Defaults |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/notifications` | Inbox list |
| `GET` | `/api/notifications/unread-count` | Badge count |
| `POST` | `/api/notifications/{id}/read` | Mark one read |
| `POST` | `/api/notifications/read-all` | Mark all read |
| `DELETE` | `/api/notifications/{id}` | Dismiss/delete |
| `GET` | `/api/notification-preferences` | User channel prefs |
| `PUT` | `/api/notification-preferences` | Update prefs |
| `POST` | `/api/notifications/broadcast` | Admin broadcast (optional) |

Domain modules do not need public “send email” endpoints — they dispatch events.

---

## Inbox list

`GET /api/notifications?unread_only=1&page=`

```json
{
  "success": true,
  "data": [
    {
      "id": 9001,
      "type": "leave.approved",
      "title": "Leave approved",
      "body": "Your leave request was approved.",
      "data": { "leave_request_id": 44 },
      "read_at": null,
      "created_at": "2026-07-16T09:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 3, "last_page": 1 }
}
```

---

## Preferences

`PUT /api/notification-preferences`

```json
{
  "email": true,
  "push": false,
  "system": true,
  "categories": {
    "leave": { "email": true, "system": true },
    "payroll": { "email": true, "system": true }
  }
}
```

Mandatory HR notices may ignore suppress flags per policy.

---

## Error Codes

| Code | When |
|------|------|
| `NOTIFICATION_NOT_FOUND` | Missing/unauthorized id |
| `NOTIFICATION_BROADCAST_FORBIDDEN` | Lacks broadcast permission |

---

## Related

- [LEAVE_API.md](./LEAVE_API.md)
- [PAYROLL_API.md](./PAYROLL_API.md)
- [../01-architecture/EVENT_FLOW.md](../01-architecture/EVENT_FLOW.md)
