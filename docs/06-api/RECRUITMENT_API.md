# Recruitment API

> Job openings, candidates, interviews, evaluations, offers, hiring handoff.
>
> Business: [../02-business/recruitment/README.md](../02-business/recruitment/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/job-openings
/api/candidates
/api/interviews
/api/offers
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_manage_job_positions` | Job openings |
| `can_view_candidates` | View pipeline |
| `can_manage_candidates` | CRUD candidates |
| `can_manage_interviews` | Interviews |
| `can_create_offer` | Create offers |
| `can_approve_offer` | Approve offers (if dual control) |
| `can_hire_candidate` | Handoff to onboarding |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/job-openings` | List openings |
| `POST` | `/api/job-openings` | Create |
| `GET` | `/api/job-openings/{id}` | Detail |
| `PATCH` | `/api/job-openings/{id}` | Update |
| `POST` | `/api/job-openings/{id}/close` | Close opening |
| `GET` | `/api/candidates` | List (`job_opening_id`, `stage`) |
| `POST` | `/api/candidates` | Create candidate |
| `GET` | `/api/candidates/{id}` | Detail |
| `PATCH` | `/api/candidates/{id}` | Update |
| `POST` | `/api/candidates/{id}/stage` | Move pipeline stage |
| `GET` | `/api/candidates/{id}/interviews` | Interviews |
| `POST` | `/api/candidates/{id}/interviews` | Schedule interview |
| `POST` | `/api/interviews/{id}/evaluation` | Submit evaluation |
| `GET` | `/api/candidates/{id}/offers` | Offers |
| `POST` | `/api/candidates/{id}/offers` | Create offer |
| `POST` | `/api/offers/{id}/send` | Mark/send offer |
| `POST` | `/api/offers/{id}/accept` | Accept → start onboarding |
| `POST` | `/api/offers/{id}/reject` | Reject offer |
| `POST` | `/api/candidates/{id}/hire` | Explicit hire handoff (if used) |

Candidates are **not** employees until onboarding/activation.

---

## Create candidate

`POST /api/candidates`

```json
{
  "job_opening_id": 4,
  "full_name": "Chris Lee",
  "email": "chris@example.test",
  "phone": "+84000000000",
  "stage": "applied"
}
```

---

## Offer accept

`POST /api/offers/{id}/accept`

```json
{
  "accepted_at": "2026-07-20T10:00:00Z"
}
```

**200** → triggers Onboarding via service/event (`OfferAccepted`).  
Response may include `onboarding_case_id`.

---

## Stages (logical)

`applied` → `screening` → `interview` → `offer` → `hired` | `rejected` | `withdrawn`

---

## Error Codes

| Code | When |
|------|------|
| `CANDIDATE_INVALID_STAGE` | Illegal stage move |
| `OFFER_NOT_PENDING` | Accept/reject wrong state |
| `JOB_OPENING_CLOSED` | Cannot add candidates |

---

## Related

- [ONBOARDING_API.md](./ONBOARDING_API.md)
- [DOCUMENT_API.md](./DOCUMENT_API.md)
- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
