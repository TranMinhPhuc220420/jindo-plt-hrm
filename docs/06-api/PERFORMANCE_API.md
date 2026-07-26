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
| `GET` | `/api/performance/review-cycles/{id}` | Detail (+ progress counts) |
| `PUT` | `/api/performance/review-cycles/{id}/participants` | Sync participants (draft only; removes goals for dropped participants) |
| `POST` | `/api/performance/review-cycles/{id}/start` | Start (requires ≥1 participant) |
| `POST` | `/api/performance/review-cycles/{id}/finalize` | Finalize |
| `DELETE` | `/api/performance/review-cycles/{id}` | Delete draft cycle |
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

## Sync participants

`PUT /api/performance/review-cycles/{id}/participants`

```json
{
  "participant_employee_ids": [10, 11, 12]
}
```

Only allowed while the cycle is `draft`. Replaces the full participant set.

Goals belonging to removed participants (same `review_cycle_id`) are deleted automatically.

---

## Visibility

- `can_manage_review_cycles`: all company cycles
- Participant employee: cycles they belong to
- `can_evaluate_employee` manager: cycles that include their direct reports
- Others: `PERFORMANCE_FORBIDDEN_SCOPE` on show / related goal & evaluation lists for that cycle

---

## Delete draft cycle

`DELETE /api/performance/review-cycles/{id}`

Only `draft` cycles. Deletes associated goals; participants/evaluations cascade via FK.

---

## Start cycle

`POST /api/performance/review-cycles/{id}/start`

Requires at least one participant. Empty cycles return `REVIEW_CYCLE_NO_PARTICIPANTS`.

Cycle detail / list include progress fields:

- `participants_count`
- `evaluations_count`
- `goals_active_count`
- `goals_completed_count`
- `participants` (detail; id + name/code)

---

## Goals

When `review_cycle_id` is set, the employee must be a cycle participant (`PERFORMANCE_FORBIDDEN_SCOPE` otherwise).

`PATCH /api/performance/goals/{id}` accepts `progress` (0–100) and `status` (`active` | `completed` | `cancelled`).

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

Employee must be a participant; cycle must be `active`.

---

## Promotion suggestions

`GET /api/performance/promotion-suggestions?review_cycle_id={id}` filters by cycle.

---

## Error Codes

| Code | When |
|------|------|
| `REVIEW_CYCLE_NOT_OPEN` | Submit outside window |
| `REVIEW_CYCLE_NO_PARTICIPANTS` | Start with zero participants |
| `EVALUATION_DUPLICATE` | Already submitted |
| `PERFORMANCE_FORBIDDEN_SCOPE` | Not participant / not manager-HR of subject |
| `PERFORMANCE_INVALID_TRANSITION` | Invalid cycle status change (incl. sync on non-draft) |

---

## Related

- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
- [REPORT_API.md](./REPORT_API.md)
