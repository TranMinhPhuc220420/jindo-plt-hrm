# Coverage Baseline

> PHP coverage floor for CI. Raise gradually; never lower without team agreement.

## How to refresh locally

```bash
# Requires pcov or xdebug
php artisan test --coverage --min=0
```

Record overall `%` and hotspots under `app/Policies`, `app/Services`, `app/Http/Controllers`.

## Floor (initial)

| Scope | Minimum line coverage |
|-------|------------------------|
| `app/` (overall) | **40%** (raise toward 50–60% after first measured CI run) |
| Guidance: `app/Policies` + critical Services | Prefer higher; raise floor in later sprints |

CI fails when coverage drops below the overall floor (`--min=40`).

## History

| Date | Overall | Notes |
|------|---------|-------|
| 2026-09-08 | ≥40% gate | Initial CI floor via `php artisan test --coverage --min=40`; bump after first green coverage report |

Update this table when intentionally raising the floor.
