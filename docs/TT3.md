# TT3: Troop Tracker Version 3 Architecture

---

## TL;DR — What Every Developer Must Know Before Touching This Code

### Backend (Laravel/PHP)

- **There is no `User` — only `Trooper`.** Every auth reference uses `Trooper`. Never introduce `User`.
- **One gateway endpoint handles everything.** All requests go through a single controller that routes by message type. Do not create new controllers for features.
- **Commands mutate; Queries read.** If it changes data, it is a Command. If it only reads, it is a Query. Keep them separate and never blur the line.
- **Business logic belongs in Handlers/Services — never in controllers, jobs, or console commands.** Controllers are transport wiring only.
- **Jobs and console commands orchestrate; they do not own logic.** A job should call a service or dispatch a message, not contain `if` chains and database calls.
- **Queues are asynchronous by design.** Notification delivery (email, push) is queued and runs in the background. Never assume a job has completed immediately after dispatch — it has not.
- **Transactions wrap Commands.** If your command touches multiple tables and one step fails, they should all roll back. This is should be enforced at with the message-handler.

### Frontend (Svelte 5/TypeScript)

- **Everything that talks to the backend is `async/await`.** Network calls return Promises; they do not block. If you write synchronous-looking code expecting an immediate response, it will not work. Always `await` gateway calls or handle the Promise explicitly.
- **One shared gateway adapter, always.** Do not reach out to the API directly in a component. All requests go through the shared gateway client that attaches auth tokens and normalizes responses.
- **Route files (`+page.ts`) are orchestration only.** They load data and dispatch actions. Domain/feature logic lives in `src/lib/domains`.
- **Feature modules group queries and commands.** Auth calls live in an auth module, event calls in an events module, etc. Do not scatter gateway calls across page files.
- **Svelte 5 uses `$props()` typed with a colon, not a cast.** Use `let { ... }: Props = $props();` — the cast form (`as Props`) breaks external prop inference.

### The Golden Rules

1. Keep controllers, components, basically "files" thin.
2. Never block the UI thread — always `async`/`await`.
3. Never put business logic where transport belongs, or transport logic where domain belongs.

---

## 1. Purpose

TT3 defines a unified architecture for Troop Tracker where:

- Laravel is a headless backend.
- One REST gateway endpoint handles all app requests.
- Command and Query responsibilities remain explicit (CQRS).
- Svelte 5 is the shared frontend platform for web and mobile.
- Notifications are multi-channel: email, browser push, and mobile push.

This document is the single source of truth for TT3 architecture in this repository.

---

## 2. Core Architectural Direction

### 2.1 Backend Shape

- Backend runtime: Laravel (API-first/headless).
- External API shape: single gateway endpoint.
- Internal execution model: CQRS with Message + Handler separation.
- Controller role: single gateway controller routing to messages + handlers, no business logic.

### 2.2 Frontend Shape

- Frontend runtime: Svelte 5.x.
- Delivery targets:
  - Web application (SPA experience).
  - Mobile applications via Capacitor wrapper around shared Svelte app.
- Shared API adapter used by web and mobile clients.

### 2.3 Notification Shape

- Channels at launch:
  - Email notifications.
  - Browser push notifications.
  - Mobile push notifications.
- Delivery fan-out happens in backend notification orchestration services.

---

## 3. Single Gateway Contract

### 3.1 Endpoint

- The TT3 application exposes one REST entrypoint for application operations.
- All commands and queries are sent through this gateway.

### 3.2 Request Envelope

Every request uses the same JSON envelope:

```json
{
  "type": "Namespace.MessageClassName",
  "payload": {}
}
```

### 3.3 Contract Rules

- `type` identifies the command/query message to execute.
- `payload` contains message input data.
- The gateway uses a resolver that maps namespace segments to the message folder structure.
- If a message name starts with `Get`, the resolver maps it to
  `Namespace/Queries/GetMessageClassName`.
- Otherwise, the resolver maps it to `Namespace/Commands/MessageClassName`.
- Authenticated Trooper identity is injected by the gateway into execution context.

### 3.4 Response Envelope

Message Result format:

```typescript
interface MessageResult
{
  success: boolean;
  status: "success" | "failure" | "validation";
  message?: string;
  data?: {};
  errors?: [];
}
```

Success Message

```json
{
  "success": true,
  "status": "success",
  "data": {},
}
```

Validation Message

```json
{
  "success": false,
  "status": "validation",
  "message": "Validation Message",
  "errors": []
}
```

Failure Message

```json
{
  "success": false,
  "status": "failure",
  "message": "Failure Message",
}
```



### 3.5 Error Semantics

- Validation failure: HTTP 422.
- Authorization failure: HTTP 403.
- Message type not found/unsupported: HTTP 404 or 400 (implementation policy).
- Unexpected exception: HTTP 500.

---

## 4. CQRS Execution Model (TT3)

### 4.1 Pattern

TT3 keeps explicit CQRS semantics:

- Commands mutate state.
- Queries read state and must not mutate state.

### 4.2 Internal Structure

TT3 keeps the current proven internal split:

- Message objects represent intent and typed input.
- Handlers own domain logic and orchestration.

This preserves existing strengths:

- Testability (isolated handler tests).
- Reuse from jobs/commands/orchestrators.
- Thin controller boundary.

### 4.3 Gateway to Bus Flow

1. Gateway validates transport envelope.
2. Gateway resolves message type via the namespace-to-folder resolver.
3. Gateway creates message from payload.
4. Gateway injects authenticated Trooper context.
5. Gateway executes authorization checks.
6. Gateway dispatches message to the bus/handler.
7. Gateway returns standardized envelope.

### 4.4 Transaction Boundary

- Command execution runs inside a transaction boundary when state consistency is required.
- Query execution does not open write transactions.
- Transaction policy is enforced at dispatch/orchestration level, not in controllers.

---

## 5. ADR Boundaries in TT3

### 5.1 Action (Controller)

- The gateway controller is invokable and transport-focused.
- Responsibilities:
  - Deserialize request envelope.
  - Authenticate Trooper.
  - Perform transport-level validation.
  - Dispatch message.
  - Serialize response envelope.

### 5.2 Domain

- Business logic remains in domain handlers/services.
- Jobs and console commands orchestrate by dispatching messages.
- Domain code remains independent of HTTP concerns.

### 5.3 Responder

- For API calls, responder output is standardized JSON envelope.

---

## 6. Frontend Architecture: Svelte 5 for Web + Mobile

### 6.1 Shared Application Strategy

- A single Svelte 5 codebase powers both web and mobile UI.
- Capacitor wraps the same built frontend for iOS/Android.
- Client-side route groups separate authenticated app surfaces from public routes.

### 6.2 Shared Gateway Client Adapter

The frontend uses one gateway adapter function conceptually equivalent to:

- Input: `type`, `payload`.
- Behavior:
  - Attach auth token.
  - POST to gateway.
  - Parse standardized response envelope.
  - Handle auth expiry consistently.
- Output: typed success/error result.

### 6.3 Identity and Session

- Trooper authentication state is managed in shared client state.
- Web and mobile use the same message contract.
- Mobile token persistence uses device-safe storage via Capacitor plugins.

### 6.4 Deep Linking and OAuth Return

- Mobile flows use deep/universal links to return from identity providers.
- Callback parsing occurs in shared app shell logic.
- Parsed credentials/codes are exchanged through gateway messages.

### 6.5 Frontend Implementation Pattern (Recommended)

- The Svelte client should use a feature-first structure, not a route-mirrored lib structure.
- Frontend CQRS calls should flow through one shared gateway adapter.
- The route layer should remain orchestration-only:
  - +page.ts and +layout.ts hydrate read models via query functions.
  - UI actions dispatch command functions and then refresh or invalidate state.
- Domain message calls should be grouped by feature (for example: auth, events, notices) with explicit query and command modules.
- Classic repository pattern is optional and should be thin if used:
  - Repositories may wrap feature modules for naming consistency.
  - Repositories should not become ORM-like abstractions or duplicate backend domain logic.
- Shared auth/session handling (token attach, expiry behavior, envelope parsing, error normalization) must live in the gateway layer so web and mobile behave identically.
- UI state modules manage presentation/session state only and should not perform transport calls directly.

---

## 7. Notification Architecture (Email + Browser + Mobile)

### 7.1 Launch Channels

TT3 supports three notification channels at launch:

- Email (transactional and digest patterns as applicable).
- Browser push.
- Mobile push.

### 7.2 Event-Driven Notification Flow

1. Domain command completes state change (for example, event/troop creation or update).
2. Notification intent is emitted to notification orchestration.
3. Orchestration resolves target Troopers and channel preferences.
4. Channel adapters fan out delivery:
   - Email adapter.
   - Browser push adapter.
   - Mobile push adapter.
5. Delivery outcomes are recorded for observability and retries.

### 7.3 Preferences Model

- Preferences are owned per Trooper.
- Channel enablement and delivery frequency are independent per channel.
- Organization-scoped preference overlays remain supported where required.

### 7.4 Reliability Expectations

- Queue-backed asynchronous delivery for non-blocking UX.
- Retry strategy per channel adapter.
- Dead-letter/failure tracking for operational visibility.

---

## 8. Security and Governance

### 8.1 Type Resolution Safety

- Gateway `type` values must be mapped through a server-side namespace-to-folder resolver.
- Arbitrary runtime class construction from client input is not allowed.

### 8.2 Authorization Ownership

- Authorization is evaluated in domain-level message/handler execution paths.
- Gateway enforces authentication presence before dispatch.

### 8.3 Input Validation Ownership

- Envelope validation (shape/type/payload existence) occurs at gateway boundary.
- Business validation occurs in message/domain validation paths.

### 8.4 Observability

- All gateway calls include correlation IDs for tracing.
- Structured logs include message type, status, duration, and failure code.
- Sensitive payload fields are redacted in logs.

---

## 9. Domain Vocabulary and Conventions

TT3 retains project domain language and conventions:

- Authenticated entity is Trooper (never User).
- Domain nouns: Trooper, Event/Troop, Costume, Organization, Notice.
- Controllers remain thin ADR Actions.
- Business logic remains in domain handlers/services.
- Command/query naming and routing remain convention-driven.

---

## 10. Architecture Decision Record: Handler Split

### Decision

TT3 keeps Message + Handler separation internally while adopting a unified external gateway envelope.

### Why

- Aligns with existing codebase architecture and team conventions.
- Improves maintainability by avoiding transport-domain coupling.
- Preserves composability for jobs and console orchestration.
- Enables high-quality unit and feature testing boundaries.

### Non-Selected Option

Self-handling message classes were considered but are not selected for TT3 because they merge too many concerns into one class and reduce internal composability.

---

## 11. Non-Goals

- This document does not define phased rollout timelines.
- This document does not prescribe migration scripts.
- This document does not split TT3 architecture across multiple documents.

TT3 architecture guidance is intentionally consolidated in this file.
