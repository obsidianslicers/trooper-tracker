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
php artisan tracker:generate-factories
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
php artisan tracker:generate-factories

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
