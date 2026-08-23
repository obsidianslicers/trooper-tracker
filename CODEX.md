# TroopTracker - Codex Reference

TroopTracker is a Laravel application for managing costuming club activities:
events, trooper profiles, memberships, achievements, notifications, and
organization hierarchies. It supports multiple organizations with role-based
access control.

The Laravel app root is `tracker-app/`. Run project commands from there unless
the user asks otherwise.

---

## How Codex Should Work Here

- Read the surrounding code before changing behavior.
- Keep changes small and aligned with existing Laravel, Blade, Alpine, HTMX, and
  Bootstrap patterns.
- Prefer existing feature folders, request classes, policies, services, and
  helpers over new abstractions.
- Do not edit generated base models in `app/Models/Base/`.
- Preserve user changes in the working tree; do not revert unrelated files.
- Use `rg` for searching and `apply_patch` for hand edits.
- Run focused tests or quality checks when the change has enough risk to justify
  them, and report anything that could not be run.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.2+, Laravel |
| Database | MySQL 8.0+; SQLite in-memory for tests |
| Frontend | Blade templates, Alpine 3.x, HTMX 2.x, Bootstrap 5.2x |
| Build | Vite, npm |
| Testing | PHPUnit |
| Code quality | Pint, PHPStan |

---

## Architecture

### Action-Domain-Responder

- **Action**: Thin invokable controllers. Validate input, dispatch through
  MagicBus, and return a response.
- **Domain**: Business logic lives in `app/Features/` handlers.
- **Responder**: Controllers format handler results into Blade views, redirects,
  or JSON.

Controllers should orchestrate only. Do not put business logic in them.

### MagicBus Command/Query Separation

MagicBus resolves handlers by convention:

- `CreateEventCommand` -> `CreateEventCommandHandler`
- `GetTroopersByRoleQuery` -> `GetTroopersByRoleQueryHandler`

Commands are write operations. Queries are read operations and should not create
side effects.

Handler modifier traits:

- `ShouldBeTransactional` wraps the handler in a database transaction.
- `ShouldRunAfterResponse` defers execution until the HTTP response is sent.

Jobs and Artisan commands are also thin orchestrators. They should dispatch
commands or queries rather than contain domain logic.

### Feature Naming (Verb + Object)

A feature's name is `[Verb] [Object] [optional details]` (e.g. `RequestDeletion`,
`UpdateProfile`, `CancelDeletion`). Two rules:

1. Keep the same name top to bottom, where possible. The ViewModel, controller,
   form request, and top-level action should share one name:
   `RequestDeletionViewModel` (svelte) -> `RequestDeletionController` ->
   `RequestDeletionRequest` -> `RequestTrooperDeletion` (message). The message
   layer may swap in a more specific object noun (e.g. `Trooper` instead of the
   `Account` namespace it is called from) when that object is shared across
   multiple entry points -- `RequestTrooperDeletion` lives outside the `Account`
   namespace because both `Account` and `Admin` can trigger it. That is the one
   deliberate exception to rule 1; do not let unrelated layers drift in name
   otherwise.
2. Higher-intent (UI/HTTP) layers can be terser than lower-intent
   (message/domain) layers, as long as the verb+object pair still matches:
   UI/HTTP say `UpdateProfile` / `RequestDeletion`; the message layer says
   `UpdateTrooperProfile` / `RequestTrooperDeletion`. The extra specificity is
   added, not changed.

When adding a sibling action to an existing feature (e.g. a "cancel" counterpart
to "request"), name it the same way -- verb first, object second, matching
object noun -- rather than reusing an unrelated noun or reversing the order
(avoid e.g. `DeletionCancelController`; prefer `CancelDeletionController`).

---

## Svelte and Frontend Architecture

Core principle: decompose into small, single units of work. This reduces
cognitive load, isolates bugs, and keeps testing and support fast.

### When to use Svelte vs Blade

- Blade: static, low-interaction, or server-rendered content (legal terms,
  email-confirmation landers, simple static messages).
- Svelte: views that genuinely benefit from client-side reactivity, complex
  state, or rich UI interaction.
- Do not migrate views to Svelte for stack purity. Replacing a 10-line static
  Blade template with a Svelte page, API route, and TypeScript VM layer adds
  unnecessary overhead.
- Start new or refactored features with a small, simple page rather than a
  large one.

### Message decomposition (PageData)

Alongside Commands and Queries, page-facing endpoints get a PageData message:

- Commands and Queries stay strictly separate. Neither should be overloaded to
  also supply multi-query page view data.
- PageData aggregates multiple queries into a single response payload tailored
  to a specific page. Example: `GetAllOrganizationsQuery` returns full
  records/columns; `RegistrationPageData` pulls from that query and trims the
  payload to only the JSON fields the registration UI needs.
- Location: `app/Messages/[Feature]/PageData/[Name]PageData.php` (see
  `app/Messages/Auth/PageData/LoginPageData.php` for reference).

Calling conventions:

- Page requests: `PageData::call($request)`. It hydrates constructor
  parameters from request inputs, query strings, route parameters, and route
  model bindings.
- Everywhere else: explicit named arguments (e.g.
  `Query::call(organization_id: 123)`) to keep dependencies clear and
  refactoring safe.
- Keep PHPDoc annotations on static `::call()` methods to preserve constructor
  autocomplete.
- For messages that need the authenticated user, inject `Actor $actor` in the
  constructor. This distinguishes the active session user from target user
  records fetched via lookup.

### Svelte directory structure

```text
resources/svelte/
├── lib/domains/[feature]/
│   └── vms/
│       └── [PageViewModel].svelte.ts   # One file per VM; page logic, state, types
└── pages/[feature]/
    ├── components/                     # Single-responsibility, reusable UI components
    │   └── ComponentName.svelte
    └── PageName.svelte
```

- `lib/domains/[feature]/vms/`: each ViewModel handles a single unit of work
  supporting one specific page. Keep helpers, types, and logic in its VM file.
- `pages/[feature]/`: presentation layer only. Components and pages delegate
  business and state logic to their VM. Simple pages may not need a VM; use
  judgment.
- Reference implementation: the auth/Login module
  (`resources/svelte/lib/domains/auth/vms/LoginViewModel.svelte.ts`).

---

## Key Directory Map

```text
tracker-app/
├── app/
│   ├── Features/              # Domain logic by feature
│   │   ├── Events/
│   │   │   ├── Commands/
│   │   │   └── Queries/
│   │   ├── Troopers/
│   │   ├── Organizations/
│   │   ├── Reports/           # Queries only
│   │   ├── Notices/
│   │   └── Changes/           # Queries only
│   ├── Http/Controllers/      # Invokable single-action controllers
│   ├── Models/                # Extended Eloquent models
│   ├── Models/Base/           # Generated from schema; never edit directly
│   ├── Bus/                   # MagicBus dispatcher and interfaces
│   ├── Jobs/                  # Queueable orchestration jobs
│   ├── Console/Commands/      # Artisan orchestration commands
│   ├── Policies/              # Authorization
│   ├── Rules/                 # Custom validation rules
│   └── Services/              # Standalone services
├── tests/
│   ├── Feature/
│   └── Unit/
└── docs/
```

---

## Coding Conventions

### Naming

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `CreateEventCommandHandler` |
| Methods / functions | camelCase | `handleRequest()` |
| Parameters and local variables | snake_case | `$local_variable` |
| Class properties | snake_case | `private string $class_property` |
| Test methods | `test_` + snake_case | `test_invoke_creates_event()` |

### Style

- Add `declare(strict_types=1);` to PHP files.
- Use explicit scalar parameter and return types.
- Keep lines at or below 100 characters where practical.
- Keep methods focused; extract private helpers when a method grows too large.
- Add comments only when the reason is not obvious from the code.

### Controllers

Controllers should be invokable single-action classes:

```php
class CreateEventController extends Controller
{
    public function __invoke(CreateEventRequest $request, MagicBus $bus)
    {
        // Validate, dispatch, respond.
    }
}
```

For simple CRUD where a dedicated controller is not needed, routes may use
`MagicBusController` with command defaults.

---

## Database Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Table names | Plural `snake_case` | `troopers` |
| Column names | `snake_case` | `first_name` |
| Boolean columns | `is_`, `can_`, `has_`, `should_` prefix | `is_verified` |
| Primary key | `id` | Auto-incrementing integer |
| Foreign keys | `singular_table_id` | `trooper_id` |
| Pivot tables | Alphabetized singular names | `event_trooper` |
| Timestamps | `created_at`, `updated_at` | Eloquent standard |
| Soft deletes | `deleted_at` | SoftDeletes trait |

After schema changes, regenerate base models and factories:

```bash
php artisan code:models
php artisan fabricator:generate-factories
```

Never edit `app/Models/Base/`; it is overwritten on regeneration.

---

## Testing Guidance

| Component | Test type |
|-----------|-----------|
| Controllers | Feature |
| Jobs | Feature |
| Console commands | Feature |
| Command/query handlers | Unit |
| Policies | Unit |
| Rules | Unit |
| Services | Unit |

Test conventions:

- Subject under test is `$subject`.
- Use Mockery with `shouldReceive()` chains for mocks.
- Prefer factory states such as `asAdministrator()` and `asActive()` when
  available.
- Tests use SQLite in-memory as configured in `phpunit.xml`.

---

## Key Services

Inject via constructor or `__invoke` parameter:

| Service | Methods |
|---------|---------|
| `FlashMessageService` | `success()`, `error()`, `warning()`, `info()` |
| `BreadCrumbService` | `add(string $title, ?string $url)`, `get(): Collection` |
| `GeocodingService` | `geocode(string $address): ?array` |

---

## Common Commands

Run these from `tracker-app/`:

```bash
# Run tests
php artisan test
php artisan test --filter=TestClassName

# Code quality
./vendor/bin/pint
./vendor/bin/phpstan

# Schema changes, after migrations
php artisan code:models
php artisan fabricator:generate-factories

# Make scaffolding
php artisan make:controller NameController --invokable
php artisan make:policy NamePolicy
php artisan make:rule NameRule

# Cache in development
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Assets
npm run dev
npm run build
```

---

## Enums

Enums are backed strings using `HasEnumHelpers`:

- `AchievementType`, `AwardFrequency`, `EventStatus`, `EventTrooperStatus`,
  `EventType`
- `MembershipRole`: `member`, `moderator`, `administrator`
- `MembershipStatus`: `pending`, `active`, `retired`
- `NoticeType`, `NotificationFrequency`, `OrganizationType`, `TrooperTheme`

Useful helpers:

```php
MembershipRole::toArray();
EventStatus::toValidator();
```

---

## Authorization

Policies live in `app/Policies/` and use `HasTrooperPermissionsTrait`.

```php
$this->authorize('update', $event);
```

Available policies include `AwardPolicy`, `EventPolicy`, `NoticePolicy`,
`OrganizationPolicy`, and `TrooperPolicy`.

---

## Full Documentation

| Topic | File |
|-------|------|
| Architecture deep-dive | `docs/ARCHITECTURE.md` |
| Coding conventions | `docs/CODING_CONVENTIONS.md` |
| Project directory structure | `docs/PROJECT_STRUCTURE.md` |
| Database schema and ERD | `docs/DATABASE.md` |
| Environment variables | `docs/ENVIRONMENT_VARIABLES.md` |
| Authentication flow | `docs/AUTHENTICATION.md` |
| Console commands reference | `docs/COMMANDS.md` |
| Deployment | `docs/DEPLOY.md` |
