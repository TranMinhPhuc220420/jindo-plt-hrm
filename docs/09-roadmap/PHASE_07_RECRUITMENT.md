# Phase 07 — Recruitment & Operations

> Recruitment, onboarding, documents, and assets (Hire & Ops).
>
> Master: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)  
> Maps to PROJECT_LOGIC Phase 4

---

## Goal

Close the loop from candidate → offer → onboarding → active employee, with document storage and asset custody for join/exit.

---

## In Scope

### Recruitment

- Job openings, candidates, stages
- Interviews + evaluations
- Offers send/accept/reject
- Handoff event to onboarding

### Onboarding

- Cases from accepted offer (or manual HR start)
- Checklist tasks (account, docs, equipment, training, probation)
- Completion → employee steady state

### Documents

- Metadata + private file upload/download
- Company + employee categories
- Permission-gated access

### Assets

- Inventory CRUD
- Assign / return (audited)
- Damage report + basic maintenance/replace

---

## Out of Scope

- AI resume screening (Future)
- E-signature (Future)
- Full CMMS / ATS replacement depth

---

## Dependencies

- Phase 02 Employee (required)
- Phase 01 Auth (account creation during onboarding)
- Documents/Assets APIs as implemented in this phase

---

## Key Docs

- [RECRUITMENT_API.md](../06-api/RECRUITMENT_API.md)
- [ONBOARDING_API.md](../06-api/ONBOARDING_API.md)
- [DOCUMENT_API.md](../06-api/DOCUMENT_API.md)
- [ASSET_API.md](../06-api/ASSET_API.md)
- [FILE_STORAGE.md](../01-architecture/FILE_STORAGE.md)
- Business: `docs/02-business/recruitment|onboarding|document|asset/`

---

## Exit Criteria

- [x] Offer accept creates/starts onboarding
- [x] Mandatory onboarding tasks block completion
- [x] Employee files upload/download authorized
- [x] Asset assign/return audited
- [x] No candidate rows treated as employees before activation
- [x] Milestone toward `v0.5.0`

---

## Suggested Internal Order

1. Documents (needed by others)
2. Assets
3. Recruitment pipeline + offers
4. Onboarding checklist + completion
5. Exit-oriented return asset checklist (light)
