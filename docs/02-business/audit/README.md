# Audit

> Traceability for important actions.
>
> Principles: [DEVELOPMENT_PRINCIPLES.md](../../00-overview/DEVELOPMENT_PRINCIPLES.md)

---

## Purpose

Append-only audit log so HR/admin can answer “who changed what, when?” for sensitive operations.

---

## Minimum audited actions

- Employee edited
- Salary changed
- Attendance approved
- Leave rejected
- Asset assigned

(Extend as modules ship.)

---

## Rules

1. Append-only in normal application flows.
2. Actor + action + subject + company + timestamp + context payload.
3. Domain modules emit audit via writer/listener — not optional for listed actions.
4. Viewing audit logs requires elevated permission.

---

## Permissions

| Permission | Intent |
|------------|--------|
| `can_view_audit_logs` | Browse audit trail |

---

## Related

- [../../03-database/DATABASE_CONVENTIONS.md](../../03-database/DATABASE_CONVENTIONS.md)
- [../../01-architecture/EVENT_FLOW.md](../../01-architecture/EVENT_FLOW.md)
- [../../06-api/AUDIT_API.md](../../06-api/AUDIT_API.md)
