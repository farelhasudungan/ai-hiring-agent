# Application Architecture

## Purpose and status

This project is an AI-assisted recruitment and applicant tracking application.
Its intended workflow is to publish jobs, collect applications and documents,
screen candidates against job requirements, and help recruiters review results.
AI provides recommendations; recruiters retain control of hiring decisions.

This document records the proposed architecture from the project discussion.
It is implementation context, not a claim that these workflows already exist.
The current codebase contains hiring and form models, migrations, and factories;
the web entry point still renders Laravel's default welcome page. The database
tables and relationships are documented in
[database-schema.md](database-schema.md).

The target is a Laravel monolith with a lightweight Service–Repository pattern
and queued background processing. Separate deployable services are not required.

## Layer responsibilities

```text
HTTP request
    |
Routing / middleware / Form Request / authorization
    |
Controller
    |
Service -----------------------> AiService (external AI API)
    |
Repository
    |
Eloquent model
    |
Database

Queued job --------------------> Service
Console command ---------------> Service
```

| Layer | Responsibility | Must not own |
| --- | --- | --- |
| Form Request | Validate input structure and field constraints; authorize the request where appropriate | Database workflows or AI calls |
| Policy / authorization boundary | Check whether the actor may perform the operation | Persistence or HTTP response formatting |
| Controller | Receive validated input, invoke the operation, return a view or response | Business rules or complex queries |
| Service | Enforce business rules, coordinate operations, own transaction boundaries | HTTP request/response dependencies |
| Repository | Encapsulate meaningful queries and persistence operations | Hiring decisions, notifications, or AI calls |
| Eloquent model | Represent stored data, relationships, casts, and reusable query scopes | End-to-end workflow orchestration |
| Job | Schedule background execution, retries, and terminal failure handling | A second implementation of business rules |
| AiService | Communicate with the external AI API | Application status transitions or database workflows |

Request validation and business validation are different. A request can contain
valid fields while the operation is invalid because the job has closed. Services
must enforce these business rules when called from HTTP, jobs, or commands.
Non-HTTP entry points must also establish the appropriate authorization or
trusted system context; using a service is not an authorization bypass.

## Folder structure

Keep Laravel's standard layout. The files below are examples to create only when
needed, not a list of existing files or required scaffolding.

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── JobPostingController.php
│   │   └── ApplicationController.php
│   └── Requests/
│       ├── StoreJobPostingRequest.php
│       └── StoreApplicationRequest.php
├── Services/
│   ├── ApplicationService.php
│   ├── ScreeningService.php
│   └── AiService.php
├── Repositories/
│   ├── ApplicationRepository.php
│   └── ScreeningRepository.php
├── Jobs/
│   └── ScreenApplicationJob.php
├── Models/
│   └── ... existing models
├── Policies/
│   └── ApplicationPolicy.php
└── Providers/
    └── AppServiceProvider.php

resources/views/
├── layouts/
├── jobs/
└── applications/

routes/web.php
tests/
├── Feature/
└── Unit/
```

## Example workflow

1. `StoreApplicationRequest` validates input; authorization checks access.
2. `ApplicationController` calls `ApplicationService`.
3. `ApplicationService` checks business rules and uses `ApplicationRepository`
   to save the application and related records in a transaction.
4. Dispatch `ScreenApplicationJob` after commit, then return confirmation.
5. The job calls `ScreeningService`, which uses `AiService` to call the AI API
   and `ScreeningRepository` to save the result.
6. A recruiter reviews the result and decides the next step.

`ScreeningService` owns our screening workflow; `AiService` only handles the
external provider. Keep AI calls outside database transactions.

## Keep it simple

- Start with flat folders. Add feature subfolders only when navigation gets hard.
- Create classes when implementing a feature; do not create empty placeholders.
- No generic `BaseService` or `BaseRepository`.
- Concrete classes are fine. Add interfaces only when they provide real value.
- Do not create a repository for every model; related records can share one.
- Keep small reusable queries as model scopes instead of wrapping every Eloquent call.
- Avoid services that only forward unchanged CRUD calls.
- Use services consistently; do not add Actions, UseCases, or Managers for the same work.
- Keep company scoping and authorization explicit; extra layers do not provide security automatically.
