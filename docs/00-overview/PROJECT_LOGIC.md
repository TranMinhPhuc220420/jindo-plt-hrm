# HRM Project Logic

> Master document describing the complete business domain, system architecture, development philosophy, and module relationships for the HRM (Human Resource Management) platform.

---

# 1. Project Overview

## Vision

Build a modern Human Resource Management (HRM) platform for small and medium businesses.

The platform should provide a complete employee lifecycle management system including:

- Recruitment
- Employee onboarding
- Attendance
- Leave Management
- Shift Scheduling
- Payroll
- Performance Evaluation
- Asset Management
- Company Documents
- Notifications
- Reporting
- Organization Structure

The project is developed using:

Backend

- Laravel
- MySQL
- REST API

Frontend

- React
- TypeScript
- Vite
- TailwindCSS

Target Platforms

- Desktop Web
- Mobile Web

Future

- React Native App
- Desktop Application
- Multi-company SaaS

---

# 2. Core Design Principles

The system must follow these principles.

## Modular

Every feature must be isolated into its own module.

Example

Attendance must not directly depend on Payroll.

Payroll should consume Attendance data through services.

---

## Scalable

Every module should be replaceable without affecting others.

Examples

Attendance

Current:

Manual Check-in

Future:

GPS Check-in
Face Recognition
Fingerprint Device
QR Code

Payroll

Current:

Monthly Salary

Future:

Hourly Salary
Daily Salary
Commission
Piece Rate

---

## Multi-company Ready

Although version 1 may only support one company, every architecture decision should assume:

Company
    ├── Departments
    ├── Employees
    ├── Assets
    ├── Payroll
    ├── Attendance
    └── Reports

---

## Permission First

Every resource must be protected by Roles and Permissions.

Never hardcode permissions.

Example

HR

can_create_employee

Manager

can_approve_leave

Employee

can_view_salary

---

## Auditability

Every important action should be traceable.

Examples

Employee edited

Salary changed

Attendance approved

Leave rejected

Asset assigned

---

# 3. High-Level Domain

The HRM system is divided into the following domains.

Core

Authentication

Authorization

Organization

Employee

Attendance

Leave

Shift

Payroll

Recruitment

Onboarding

Performance

Assets

Documents

Notifications

Reports

Settings

System

Audit Logs

Each domain should eventually have its own documentation.

---

# 4. Employee Lifecycle

The employee lifecycle is the backbone of the entire project.

Candidate

↓

Recruitment

↓

Interview

↓

Offer

↓

Accepted

↓

Onboarding

↓

Employee

↓

Attendance

↓

Leave

↓

Payroll

↓

Performance

↓

Promotion

↓

Transfer

↓

Resignation

↓

Exit Process

↓

Archived Employee

Every module revolves around this lifecycle.

---

# 5. Organization Structure

Company

↓

Branch

↓

Department

↓

Team

↓

Position

↓

Employee

Employees may also have:

Manager

Direct Supervisor

HR Owner

Department Head

---

# 6. Core Business Modules

## Authentication

Responsibilities

Login

Logout

Forgot Password

Reset Password

Two-factor Authentication

Remember Login

Session Management

---

## Authorization

Responsibilities

Role

Permission

Policy

Access Control

Feature Visibility

Menu Visibility

Action Authorization

---

## Employee

Responsibilities

Employee Profile

Emergency Contact

Education

Work History

Family

Documents

Contracts

Bank Information

Insurance

Tax Information

Status

---

## Attendance

Responsibilities

Check-in

Check-out

Working Hours

Late

Early Leave

Overtime

Break Time

Attendance Correction

Attendance Approval

Attendance History

Attendance Summary

---

## Leave

Responsibilities

Leave Request

Leave Type

Leave Balance

Leave Approval

Holiday

Weekend Rules

Compensation Leave

Half-day Leave

Hourly Leave

---

## Shift

Responsibilities

Shift Definition

Shift Assignment

Working Calendar

Rotating Shift

Night Shift

Flexible Shift

Overtime Rule

---

## Payroll

Responsibilities

Salary

Allowance

Bonus

Deduction

Tax

Insurance

Overtime

Payroll Calculation

Payroll Approval

Payslip

Payroll History

---

## Recruitment

Responsibilities

Job Position

Candidate

Interview

Evaluation

Offer

Hiring

---

## Onboarding

Responsibilities

Checklist

Account Creation

Equipment Assignment

Orientation

Training

Probation

Completion

---

## Performance

Responsibilities

Goals

KPI

OKR

Evaluation

Review Cycle

Promotion Suggestion

---

## Asset Management

Responsibilities

Assign Asset

Return Asset

Asset Inventory

Maintenance

Damage Report

Replacement

---

## Documents

Responsibilities

Company Files

Employee Files

Policies

Templates

Contracts

Certificates

---

## Notifications

Responsibilities

Email

System Notification

Push Notification

Reminder

Scheduled Notification

---

## Reports

Responsibilities

Attendance Reports

Payroll Reports

Leave Reports

Employee Reports

Department Reports

Performance Reports

Custom Reports

---

# 7. Module Relationships

Authentication

↓

Authorization

↓

Organization

↓

Employee

↓

Attendance
Leave
Shift
Payroll
Performance
Assets

↓

Reports

↓

Dashboard

Every dependency should flow downward.

Avoid circular dependency.

---

# 8. System Layers

Presentation Layer

↓

API Layer

↓

Application Services

↓

Domain Services

↓

Repositories

↓

Database

Business rules belong inside Services.

Controllers should remain thin.

---

# 9. Development Phases

Logical delivery groups (product):

| Logic phase | Scope |
|-------------|--------|
| Phase 1 — Foundation | Authentication, Authorization, Organization, Employee, Settings |
| Phase 2 — Time | Attendance, Shift, Leave, Holiday |
| Phase 3 — Payroll | Payroll, Allowance, Bonus, Deduction |
| Phase 4 — Hire & Ops | Recruitment, Onboarding, Documents, Assets |
| Phase 5 — Insight | Performance, Reports, Notifications, Audit Logs |
| Phase 6 — Advanced | Analytics, Dashboard polish, AI Assistant, Workflow Automation, Public API, Mobile App |

**Implementation roadmap** (finer slices used for execution) lives in `docs/09-roadmap/`:

| Roadmap doc | Maps to logic phase |
|-------------|---------------------|
| PHASE_01_FOUNDATION | Phase 1 (minus full Employee) |
| PHASE_02_EMPLOYEE | Phase 1 (Employee) |
| PHASE_03_ATTENDANCE + PHASE_04_LEAVE + PHASE_05_SHIFT | Phase 2 (prefer Shift before full Attendance OT rules) |
| PHASE_06_PAYROLL | Phase 3 |
| PHASE_07_RECRUITMENT | Phase 4 |
| PHASE_08_PERFORMANCE (Insight) | Phase 5 |
| FUTURE_FEATURES | Phase 6+ |

See [MASTER_ROADMAP.md](../09-roadmap/MASTER_ROADMAP.md).

---

# 10. Documentation Structure

This document is the root business/architecture source of truth.

Canonical tree (see also [docs/README.md](../README.md)):

```
docs/
  README.md
  00-overview/          # vision, scope, principles, glossary, PROJECT_LOGIC
  01-architecture/      # system, stack decision, auth, deps, layers
  02-business/          # per-module business rules
  03-database/          # naming, ERD, migrations, seeding
  04-backend/           # Laravel services, policies, testing
  05-frontend/          # React structure, UI guidelines
  06-api/               # REST standard + module APIs
  07-uiux/              # Efficient Growth design system (+ stitch/)
  08-development/       # git, review, release, deployment
  09-roadmap/           # phased delivery
  10-ai/                # AI agent rules and workflow
```

Stack decision for implementers: [STACK_DECISION.md](../01-architecture/STACK_DECISION.md) (REST + Sanctum SPA).

AI agents: start at [10-ai/AI_RULES.md](../10-ai/AI_RULES.md).

---

# 11. AI Development Rules

Every AI agent working on this project must follow these rules.

- Never violate business logic.
- Never duplicate existing functionality.
- Always reuse Services before creating new ones.
- Controllers must stay thin.
- Business logic belongs in Services.
- Validation belongs in Form Requests.
- React components should remain reusable.
- Use TypeScript everywhere.
- Follow RESTful API conventions.
- Every feature should include authorization.
- Every important action should be auditable.
- Every module should be independently testable.
- Prefer composition over inheritance.
- Keep modules loosely coupled.
- Design for future SaaS compatibility.

---

# 12. Future Expansion

The architecture should support future features without major refactoring.

Potential future modules include:

- Multi-company SaaS
- Face Recognition Attendance
- GPS Attendance
- Fingerprint Integration
- AI Resume Screening
- AI Performance Analysis
- AI Payroll Assistant
- Workflow Builder
- Approval Engine
- E-signature
- Employee Self-Service Portal
- Mobile Application
- Public API
- Third-party Integrations
- Accounting Integration
- ERP Integration
- CRM Integration

---

# End

This document is the single source of truth describing the complete business logic of the HRM platform.

Every future document should reference this file as the project's primary business and architectural foundation.