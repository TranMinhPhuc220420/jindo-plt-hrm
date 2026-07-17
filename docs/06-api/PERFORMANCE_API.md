# Performance API

> Goals, KPI/OKR, review cycles, evaluations, promotion suggestions.
>
> Business: [../02-business/performance/README.md](../02-business/performance/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/performance/review-cycles
/api/performance/goals
/api/performance/evaluations
/api/performance/promotion-suggestions
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_performance` | View (scoped) |
| `can_manage_goals` | Goals |
| `can_evaluate_employee` | Submit evaluations |
| `can_manage_review_cycles` | Cycles |
| `can_view_promotion_suggestions` | Suggestions |
| `can_manage_performance_settings` | Framework config |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/performance/review-cycles` | List cycles |
| `POST` | `/api/performance/review-cycles` | Create cycle |
| `GET` | `/api/performance/review-cycles/{id}` | Detail |
| `POST` | `/api/performance/review-cycles/{id}/start` | Start |
| `POST` | `/api/performance/review-cycles/{id}/finalize` | Finalize |
| `GET` | `/api/performance/goals` | List goals |
| `POST` | `/api/performance/goals` | Create goal |
| `PATCH` | `/api/performance/goals/{id}` | Update goal |
| `GET` | `/api/performance/evaluations` | List evaluations |
| `POST` | `/api/performance/evaluations` | Submit evaluation |
| `GET` | `/api/performance/evaluations/{id}` | Detail |
| `GET` | `/api/performance/promotion-suggestions` | List suggestions |
| `POST` | `/api/performance/promotion-suggestions/{id}/acknowledge` | HR acknowledge |

Promotion suggestion does **not** auto-change Employee position — HR uses Employee APIs separately.

---

## Create review cycle

`POST /api/performance/review-cycles`

```json
{
  "name": "H2 2026 Review",
  "framework": "okr",
  "starts_on": "2026-07-01",
  "ends_on": "2026-12-31",
  "participant_employee_ids": [10, 11, 12]
}
```

`framework`: `goal` | `kpi` | `okr` | `mixed`.

---

## Submit evaluation

`POST /api/performance/evaluations`

```json
{
  "review_cycle_id": 2,
  "employee_id": 10,
  "overall_score": 4.2,
  "summary": "Strong delivery",
  "ratings": [
    { "criterion": "delivery", "score": 4.5 },
    { "criterion": "collaboration", "score": 4.0 }
  ]
}
```

---

## Error Codes

| Code | When |
|------|------|
| `REVIEW_CYCLE_NOT_OPEN` | Submit outside window |
| `EVALUATION_DUPLICATE` | Already submitted |
| `PERFORMANCE_FORBIDDEN_SCOPE` | Not manager/HR of subject |

---

## Related

- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
- [REPORT_API.md](./REPORT_API.md)
