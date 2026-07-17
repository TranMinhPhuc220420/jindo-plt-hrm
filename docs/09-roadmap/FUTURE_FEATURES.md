# Future Features

> Post-core expansions (PROJECT_LOGIC Phase 6 + vision items).
>
> Do not block Phase 01–08 architecture on these — but keep extension points.
>
> Vision: [PROJECT_VISION.md](../00-overview/PROJECT_VISION.md)  
> Logic §12: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)

---

## Principles for Future Work

1. Preserve module replaceability (Attendance providers, Payroll strategies).
2. Keep company as first-class scope for SaaS.
3. Prefer additive APIs (`/api/v1`) when opening a public API.
4. Still permission-first and auditable.

---

## Product / Platform

| Feature | Notes |
|---------|--------|
| Multi-company SaaS | Tenancy, billing, admin of many companies |
| Employee Self-Service Portal | Richer ESS beyond basic web |
| Native Mobile App | React Native; reuse REST contracts |
| Desktop Application | If product demands |
| Public API | Versioned, keyed, rate-limited |
| Third-party integrations | Accounting, ERP, CRM |
| Workflow Builder | Cross-module approval engine |
| Approval Engine (platform) | Replace per-module ad hoc flows gradually |
| E-signature | Contracts / offers / policies |

---

## Attendance Evolutions

| Feature | Extension point |
|---------|-----------------|
| GPS check-in | Attendance provider interface |
| Face recognition | Provider |
| Fingerprint device | Provider |
| QR code | Provider |

Current v1 remains **manual** check-in.

---

## Payroll Evolutions

| Feature | Extension point |
|---------|-----------------|
| Hourly / daily salary | `PayrollCalculation` strategy |
| Commission | Strategy |
| Piece rate | Strategy |
| AI payroll assistant | Advisory only; no silent finalize |

---

## AI Features

| Feature | Caution |
|---------|---------|
| AI resume screening | Bias/review human-in-the-loop |
| AI performance analysis | Advisory |
| AI payroll assistant | Never bypass approval |
| Analytics / smart dashboard | Read models; not write path |

---

## Insight & Automation

| Feature | Notes |
|---------|--------|
| Advanced analytics | Warehouse/OLAP optional |
| Richer dashboard | Build on Report API |
| Scheduled automation | Beyond simple reminders |
| Deep audit analytics | Retention + search |

---

## Suggested Trigger to Start Future Work

Begin Future slice only when:

- [ ] Phases 01–08 exit criteria largely met
- [ ] Production payroll + attendance stable
- [ ] Public API / mobile has clear customer demand
- [ ] Extension interfaces already used (providers/strategies)

---

## Related

- [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)
- [../00-overview/BUSINESS_SCOPE.md](../00-overview/BUSINESS_SCOPE.md) (Out of Scope table)
- [../01-architecture/DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md)
