# TT3: Troop Tracker Version 3 Architecture

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
