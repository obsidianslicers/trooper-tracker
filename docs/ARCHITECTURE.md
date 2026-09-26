# Architecture Overview

This document provides a comprehensive overview of Troop Tracker's architectural patterns, organizational structure, and design decisions.

---

## Core Architectural Principles

Troop Tracker follows **Action-Domain-Responder (ADR)** with command/query separation implemented by **Hyperdrive Messages**. The application is migrating from the older MagicBus and `app/Features` handler architecture to single-file messages organized by domain. Both patterns currently exist while workflows are migrated.

### Action-Domain-Responder (ADR)

The ADR pattern separates concerns into three distinct layers:

- **Action**: Thin, invokable controllers validate input and call Messages
- **Domain**: Message classes in `app/Messages/` contain business logic and expose a `handle()` method
- **Responder**: Controllers format Message results into HTTP responses (Inertia pages, Blade views, JSON, redirects)

This separation ensures business logic is reusable and testable independent of HTTP concerns.

### Hyperdrive Message Architecture

Hyperdrive is the target dispatch layer. A Message combines the input contract and domain operation in one class:

- **Commands**: Messages that change state (create, update, delete)
- **Queries**: Messages that read data without side effects
- **Page data**: Messages that aggregate data for a specific page
- **Dispatching**: Controllers, Jobs, Console Commands, and other Messages call `Message::call()`
- **Hydration**: Hyperdrive resolves typed constructor arguments from validated request data, route parameters, the authenticated actor, and explicit arguments

See [`tracker-app/packages/hyperdrive/README.md`](../tracker-app/packages/hyperdrive/README.md) for dispatcher behavior and parameter precedence.

### Legacy MagicBus Architecture

Some workflows still use the legacy `app/Features/` layout, where a command or query is paired with a convention-based handler and dispatched through MagicBus. This code remains supported during migration but is not the pattern for new work. New domain operations should use a Message in `app/Messages/` unless the workflow is intentionally being left unchanged until its migration is scheduled.

**Key Benefits:**
- Convention over configuration (no manual routing)
- Single responsibility (each Message does one thing)
- Dependency injection (Message constructors and `handle()` methods use Laravel's container)
- Testability (Messages can be tested in isolation or through an HTTP entry point)
- Reusability (Messages can be called from any entry point)

---

## Domain Organization

Business logic is organized by domain under `app/Messages/`:

| Domain | Purpose | Contains |
|--------|---------|----------|
| `Events/` | Event and shift management | Commands + Queries + Page data |
| `Troopers/` | Trooper profiles, membership, achievements | Commands + Queries + Page data |
| `Organizations/` | Organization hierarchy and management | Commands + Queries |
| `Reports/` | Reporting and analytics | Queries only |
| `Notices/` | Notice creation and tracking | Commands + Queries |
| `Changes/` | Audit trail and change history | Queries only |

Each domain directory contains Messages grouped by intent:
- `Commands/` - Write operations (e.g., `CreateEvent.php`)
- `Queries/` - Read operations (e.g., `GetEventsByOrganization.php`)
- `PageData/` - Aggregated data for a specific page (where needed)

Legacy domains may still have parallel implementations under `app/Features/`. Do not add new Feature handlers for migrated work.

---

## Data Layer

### Model Architecture

Troop Tracker uses a two-tier model system:

**Base Models** (`app/Models/Base/`)
- Auto-generated from database schema via Reliese Laravel
- Contains all table columns, relationships, fillable arrays
- Generated via `php artisan code:models`
- **Never edit these files** - they are regenerated on schema changes

**Extended Models** (`app/Models/`)
- Extend base models to add custom behavior
- Custom methods, scopes, accessors, mutators
- Domain-specific logic and constants
- Example: `Trooper extends Base\Trooper`

### Database Conventions

All schema elements follow strict naming conventions to leverage Eloquent auto-inference:

- **Tables**: Plural `snake_case` (`troopers`, `event_troopers`)
- **Columns**: `snake_case` (`first_name`, `event_date`)
- **Booleans**: `is_`, `can_`, `has_` prefix (`is_verified`, `has_limits`)
- **Primary Key**: `id` (auto-incrementing integer)
- **Foreign Keys**: Singular table + `_id` (`trooper_id`, `event_id`)
- **Pivot Tables**: Alphabetized singular names (`event_trooper`)
- **Timestamps**: `created_at`, `updated_at`

### Migrations & Factories

- **Migrations**: All schema changes tracked in `database/migrations/`
- **Factories**: Generated test data factories in `database/factories/`
- **Seeders**: Sample data for development in `database/seeders/`

---

## Authorization & Validation

### Policies

Resource authorization lives in `app/Policies/`:

- **AwardPolicy**: Award creation/management (admins/moderators only)
- **EventPolicy**: Event creation/management (admins/moderators only)
- **NoticePolicy**: Notice creation/management (admins/moderators only)
- **OrganizationPolicy**: Organization updates (admins and scoped moderators)
- **TrooperPolicy**: Trooper profile viewing/editing (admins and scoped moderators)

All policies use `HasTrooperPermissionsTrait` for common permission methods:
- `isAdministrator(Trooper $trooper): bool`
- `isModerator(Trooper $trooper): bool`

### Custom Validation Rules

Feature-organized validation rules in `app/Rules/`:

**Auth Rules:**
- `AtLeastOneOrganizationSelectedRule` - Ensures at least one organization selected
- `UniqueOrganizationIdentifierRule` - Validates organization-specific member IDs are unique

**Admin/Organizations Rules:**
- `UniqueCostumeNameRule` - Ensures costume names unique within organization
- `UniqueNameRule` - Ensures organization names unique among siblings

### Middleware

- Standard Laravel authentication middleware
- Custom Trooper guards for multi-provider auth
- Authorization middleware for admin/moderator routes

---

## Frontend Architecture

### Target: Inertia and Svelte

New interactive screens use Inertia and Svelte 5. Page components live under `resources/svelte/pages/`, while reusable domain and view-model code lives under `resources/svelte/lib/`. Controllers return Inertia responses or page data for the Svelte page.

Svelte components should focus on presentation and delegate page state and workflow logic to small view models or domain modules. The auth pages and their view models are the reference implementation.

### Legacy Server-Rendered Views

**Blade Templates** (`resources/views/`)
- Server-rendered views with component-based structure
- Layouts, components, partials for reusability
- Data passed from controllers/handlers

### Legacy Progressive Enhancement

**HTMX 2.x and Alpine 3.x**
- Continue to support screens that have not yet migrated
- Do not introduce new HTMX workflows when a screen is being rebuilt in Svelte
- Migrate one workflow at a time while preserving behavior and authorization boundaries

**Bootstrap 5.2x**
- UI framework with custom Imperial styling
- Responsive grid system
- Component library (modals, dropdowns, alerts)

---

## Background Processing

### Queue System

**Jobs** (`app/Jobs/`)
- Queue jobs orchestrate Messages
- Implements `ShouldQueue` for asynchronous processing
- Handle notifications, event processing, background tasks
- Example: `SendEventCreatedNotificationsJob`

**Artisan Commands** (`app/Console/Commands/`)
- Console commands orchestrate Messages
- Scheduled tasks for maintenance and notifications
- Example: `SendDailyEventNotifications`

**Queue Driver**
- Database-backed queue (default)
- Worker processes via `php artisan queue:work`
- Job retries and failure handling

### Message Execution Modifiers

Messages can use traits to modify execution behavior:

**Transactional Execution:**
```php
use Hyperdrive\Concerns\ShouldBeTransactional;

final class CreateEvent extends Message
{
    use ShouldBeTransactional;
}
```

**Deferred Execution:**
```php
use Hyperdrive\Concerns\ShouldRunAfterResponse;

final class SendNotification extends Message
{
    use ShouldRunAfterResponse;
}
```

---

## Testing Strategy

### Test Types by Component

| Component | Test Type | Why |
|-----------|-----------|-----|
| Controllers | Feature | Full HTTP request/response cycle |
| Messages | Unit/Feature | Message behavior in isolation or through its HTTP entry point |
| Jobs | Feature | Queue-specific concerns + orchestration |
| Console Commands | Feature | Argument parsing + orchestration |
| Legacy handlers | Unit | Fast, isolated coverage while a workflow remains on MagicBus |
| Policies | Unit | Authorization logic in isolation |
| Rules | Unit | Validation logic in isolation |

### Test Database

- **SQLite in-memory** for fast, isolated execution
- Configured in `phpunit.xml`
- Migrations run before each test
- Database reset between tests

### Test Conventions

- Test method names: `snake_case` with `test_` prefix
- Subject under test: Always named `$subject`
- Factory states for common scenarios (`asActive()`, `asPending()`)
- Behavior-driven assertions (test outcomes, not implementation)

---

## Configuration

**See [ENVIRONMENT_VARIABLES.md](ENVIRONMENT_VARIABLES.md) for complete `.env` reference.**

Key configuration files:

- `config/tracker.php` - Application-specific settings
- `config/auth.php` - Authentication guards and providers
- `config/services.php` - Third-party service credentials
- `config/queue.php` - Queue driver configuration

---

## Development Workflow

### Code Generation

```bash
# Generate base models after schema changes
php artisan code:models

# Generate factories from base models
php artisan fabricator:generate-factories

# Create new controllers, policies, rules
php artisan make:controller NameController --invokable
php artisan make:policy NamePolicy
php artisan make:rule NameRule
```

### Code Quality

```bash
# Format code to Laravel standards
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan

# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage
```

### Cache Management

```bash
# Clear all caches during development
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Deployment Considerations

### Production Requirements

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer 2.x
- Node.js 18.x or higher (for asset compilation)
- Queue worker process (supervisor recommended)
- HTTPS for OAuth callbacks

### Build Process

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Compile production assets
npm run build

# Run migrations
php artisan migrate --force

# Generate optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate base models if schema changed
php artisan code:models
```

### Queue Worker

Run queue worker as a supervised process:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --max-jobs=100
```

### Scheduled Tasks

Add to cron:

```cron
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

---

## Further Reading

- **[Coding Conventions](CODING_CONVENTIONS.md)** - Detailed conventions, patterns, and examples
- **[Project Structure](PROJECT_STRUCTURE.md)** - Complete directory breakdown
- **[Database Schema](DATABASE.md)** - Table reference and ERD
- **[Authentication Flow](AUTHENTICATION.md)** - Multi-provider auth details
- **[Notifications](NOTIFICATIONS.md)** - Notification system architecture
