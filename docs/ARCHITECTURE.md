# Architecture Overview

This document provides a comprehensive overview of Troop Tracker's architectural patterns, organizational structure, and design decisions.

---

## Core Architectural Principles

Troop Tracker follows **Action-Domain-Responder (ADR)** with **Command/Query Separation** enforced by the **MagicBus** pattern. All business logic lives in isolated, testable handlers organized by domain.

### Action-Domain-Responder (ADR)

The ADR pattern separates concerns into three distinct layers:

- **Action**: Thin, invokable controllers validate input and orchestrate via MagicBus
- **Domain**: Command/Query handlers in `app/Features/` contain all business logic
- **Responder**: Controllers format handler results into HTTP responses (Blade views, JSON, redirects)

This separation ensures business logic is reusable and testable independent of HTTP concerns.

### MagicBus Command/Query Separation

MagicBus is a convention-based dispatcher that routes messages to handlers automatically:

- **Commands**: Write operations that change state (create, update, delete)
- **Queries**: Read operations that fetch data without side effects
- **Handlers**: Auto-resolved by convention (`CreateEventCommand` → `CreateEventCommandHandler`)
- **Dispatching**: Controllers, Jobs, Console Commands dispatch through `MagicBus::send()`

**Key Benefits:**
- Convention over configuration (no manual routing)
- Single responsibility (each handler does one thing)
- Dependency injection (handlers resolved through Laravel's container)
- Testability (handlers can be unit tested in isolation)
- Reusability (handlers callable from any entry point)

---

## Domain Organization

Business logic is organized by feature under `app/Features/`:

| Domain | Purpose | Contains |
|--------|---------|----------|
| `Events/` | Event and shift management | Commands + Queries |
| `Troopers/` | Trooper profiles, membership, achievements | Commands + Queries |
| `Organizations/` | Organization hierarchy and management | Commands + Queries |
| `Reports/` | Reporting and analytics | Queries only |
| `Notices/` | Notice creation and tracking | Commands + Queries |
| `Changes/` | Audit trail and change history | Queries only |

Each feature directory contains:
- `Commands/` - Write operations (e.g., `CreateEventCommand.php`, `CreateEventCommandHandler.php`)
- `Queries/` - Read operations (e.g., `GetEventsByOrganizationQuery.php`, `GetEventsByOrganizationQueryHandler.php`)

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

**Admin/Troopers Rules:**
- `OrganizationLeafNodeRule` - Validates selected organization is a leaf node

### Middleware

- Standard Laravel authentication middleware
- Custom Trooper guards for multi-provider auth
- Authorization middleware for admin/moderator routes

---

## Frontend Architecture

### Server-Rendered Views

**Blade Templates** (`resources/views/`)
- Server-rendered views with component-based structure
- Layouts, components, partials for reusability
- Data passed from controllers/handlers

### Progressive Enhancement

**HTMX 2.x**
- Dynamic UI updates without full page reloads
- Out-of-band swaps for multiple page regions
- Event-driven interactions (signup, cancellation, shift selection)

**Alpine 3.x**
- Client-side reactivity for interactive components
- Form validation and dynamic behavior
- State management for complex UI interactions

**Bootstrap 5.2x**
- UI framework with custom Imperial styling
- Responsive grid system
- Component library (modals, dropdowns, alerts)

---

## Background Processing

### Queue System

**Jobs** (`app/Jobs/`)
- Queue jobs orchestrate handlers via MagicBus
- Implements `ShouldQueue` for asynchronous processing
- Handle notifications, event processing, background tasks
- Example: `SendEventCreatedNotificationsJob`

**Artisan Commands** (`app/Console/Commands/`)
- Console commands orchestrate handlers via MagicBus
- Scheduled tasks for maintenance and notifications
- Example: `SendDailyEventNotifications`

**Queue Driver**
- Database-backed queue (default)
- Worker processes via `php artisan queue:work`
- Job retries and failure handling

### Handler Modifiers

Handlers can use traits to modify execution behavior:

**Transactional Execution:**
```php
use App\Bus\Concerns\ShouldBeTransactional;

readonly class CreateEventCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;  // Wraps execution in DB transaction
}
```

**Deferred Execution:**
```php
use App\Bus\Concerns\ShouldRunAfterResponse;

readonly class SendNotificationCommandHandler implements CommandHandlerInterface
{
    use ShouldRunAfterResponse;  // Runs after HTTP response sent
}
```

---

## Testing Strategy

### Test Types by Component

| Component | Test Type | Why |
|-----------|-----------|-----|
| Controllers | Feature | Full HTTP request/response cycle |
| Jobs | Feature | Queue-specific concerns + orchestration |
| Commands | Feature | Argument parsing + console output |
| Handlers | Unit | Fast, isolated business logic tests |
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

### Environment Variables

Key configuration in `.env`:

- **Database**: Connection, credentials, driver
- **OAuth**: Google and XenForo client IDs/secrets
- **Queue**: Driver selection (database, redis, sync)
- **Mail**: SMTP configuration, from address
- **App**: Debug mode, environment, key

### Config Files

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
php artisan tracker:generate-factories

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
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
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
