# Queues

> Asynchronous jobs for notifications, exports, payroll post-processing, and other slow work.
>
> Flow: [EVENT_FLOW.md](../01-architecture/EVENT_FLOW.md) · Listeners: [LISTENERS.md](./LISTENERS.md)

---

## Purpose

Keep HTTP requests fast and resilient. Domain transactions commit first; slow or flaky I/O runs in workers.

---

## When to Queue

| Queue | Keep synchronous |
|-------|------------------|
| Email / push / SMS | Small validation + core persist |
| Payslip PDF generation | Returning payroll draft calculation result when still interactive and fast enough |
| Heavy report exports | Simple list endpoints |
| Bulk fan-out notifications | Single-row CRUD |
| External integrations (later) | Permission checks |

If unsure: if it talks to the network or generates files, queue it.

---

## Job Naming & Location

```
app/Jobs/{Module_or_Concern}/{Name}Job.php

SendLeaveApprovedEmailJob
GeneratePayslipPdfJob
BuildAttendanceReportExportJob
ProcessPayrollFinalizationJob
```

Jobs should be verbs describing work remaining, not domain facts (facts are Events).

---

## Job Design Rules

1. **Idempotent where retried** — safe if run twice
2. **Small payload** — IDs over large models
3. **Reload fresh state** inside `handle()`
4. **Respect module boundaries** — call services, not foreign tables
5. **Timeouts & retries** — set explicitly for long jobs
6. **Fail visibly** — log + monitor; alert on repeated failure
7. **No HTTP request coupling**

---

## Queue Topology (logical)

| Queue name (example) | Work class |
|----------------------|------------|
| `default` | General short jobs |
| `notifications` | Email/push/inbox fan-out |
| `payroll` | Calculation post-steps / PDFs |
| `exports` | Report generation |

Tune workers per queue in deployment. See `docs/08-development/DEPLOYMENT.md` when filled.

---

## Relationship to Events

Preferred chain:

```
Service commits
  → Domain Event (after commit)
    → Queued Listener / Job
```

Avoid controllers dispatching raw jobs for every side effect when an event expresses the outcome better — unless the job **is** the use case (explicit “export now”).

---

## Failure & Retry Policy

| Concern | Guidance |
|---------|----------|
| Transient mail/provider errors | Retry with backoff |
| Business validation failure after stale state | Fail job; do not infinite retry |
| Duplicate notification risk | Idempotency keys / “already sent” checks |
| Poison messages | After max tries → failed_jobs + inspect |

---

## Security

- Jobs run with system privileges — still enforce company scoping when loading models
- Do not print secrets in job logs
- Signed temporary download URLs for exports should expire

---

## Testing

- `Queue::fake()` in feature tests to assert push
- Separate tests for job `handle()` logic
- Use `Bus::batch` only when fan-out orchestration is truly needed

---

## Anti-Patterns

1. Doing payroll finalize entirely inside an unmonitored job with no status surface
2. Huge jobs that process “all companies” without chunking
3. Catching exceptions and returning successfully when work did not complete
4. Queueing work that must be atomically visible in the API response without a status resource

---

## Related Documents

- [EVENTS.md](./EVENTS.md)
- [LISTENERS.md](./LISTENERS.md)
- [../02-business/notification/README.md](../02-business/notification/README.md)
- [../02-business/payroll/README.md](../02-business/payroll/README.md)
- [../02-business/report/README.md](../02-business/report/README.md)
