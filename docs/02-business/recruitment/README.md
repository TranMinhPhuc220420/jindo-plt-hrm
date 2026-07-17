# Recruitment

> Job positions, candidates, interviews, evaluation, offers, and hiring handoff.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Recruitment

---

## Purpose

Manage the pipeline from open job to accepted offer. Hiring hands off to **Onboarding**, which then activates **Employee**. Candidates are not employees.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Job Position | Openings / requisitions linked to org needs |
| Candidate | Applicant records and pipeline stage |
| Interview | Interview scheduling and feedback capture |
| Evaluation | Scoring / qualitative evaluation |
| Offer | Formal offer creation and status |
| Hiring | Decision that accepted candidates proceed to onboarding |

---

## Business Rules

1. Candidates remain in Recruitment until hire handoff — do not create full employee master records prematurely.
2. Job positions are company-scoped and may reference organization structure (department/position).
3. Offer acceptance is the primary trigger toward onboarding.
4. Evaluations and interviews belong to the candidate pipeline history.
5. Sensitive candidate documents (CVs) use Documents/file storage patterns with recruitment permissions.
6. Pipeline stage changes should be traceable.

---

## Key Workflows

### Open job → hire

```
Create Job Position
  → Add/source Candidates
    → Interview(s) → Evaluation
      → Offer → Candidate accepts
        → Hiring handoff → Onboarding module starts
```

### Reject / withdraw

```
Reject candidate or withdraw offer
  → Update stage/status → Notify as configured → Retain history
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Organization (dept/position context) | Payroll |
| Documents/file storage for CV/offer files | Treating candidates as employees |
| Authorization | Attendance/Leave |
| Onboarding service for handoff after accept | |

Downward lifecycle:

```
Recruitment (Offer Accepted) → Onboarding → Employee
```

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_manage_job_positions` | Create/manage openings |
| `can_view_candidates` | View candidate pipeline |
| `can_manage_candidates` | Create/update candidates |
| `can_manage_interviews` | Schedule/capture interviews |
| `can_create_offer` | Create offers |
| `can_approve_offer` | Approve offers if dual control required |
| `can_hire_candidate` | Trigger hiring handoff |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `CandidateStageChanged` | Notify recruiters/hiring manager |
| `OfferSent` | Notify candidate (email) |
| `OfferAccepted` | Start onboarding via Onboarding service |
| `CandidateHired` | Audit; close pipeline item |

---

## Out of Scope / Future

- AI resume screening
- Public careers portal as a full product surface
- External ATS sync

---

## Related Documents

- [../onboarding/](../onboarding/)
- [../employee/](../employee/)
- [../document/](../document/)
- `docs/06-api/RECRUITMENT_API.md`
