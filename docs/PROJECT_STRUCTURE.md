# Project Structure

This document maps the Troop Tracker directory structure and file organization.

**For architectural patterns and design decisions, see [ARCHITECTURE.md](ARCHITECTURE.md).**

## Directory Overview

```
tracker-app/
├── app/
│   ├── Bus/                    # MagicBus command/query dispatcher
│   ├── Console/                # Artisan commands
│   ├── Contracts/              # Interface definitions
│   ├── Enums/                  # Backed enums with helpers
│   ├── Facades/                # Laravel facades
│   ├── Features/               # Domain-organized business logic
│   ├── Generators/             # Code generation utilities
│   ├── Http/                   # Controllers and middleware
│   ├── Jobs/                   # Queue jobs
│   ├── Mail/                   # Mailable classes
│   ├── Models/                 # Eloquent models (extended)
│   │   ├── Base/              # Auto-generated base models (DO NOT EDIT)
│   │   ├── Concerns/          # Model traits
│   │   ├── Pivots/            # Custom pivot models
│   │   └── Scopes/            # Query scope traits
│   ├── Policies/               # Authorization policies
│   ├── Providers/              # Service providers
│   ├── Rules/                  # Validation rules
│   ├── Services/               # Standalone service classes
│   └── helpers.php             # Global helper functions
├── bootstrap/                  # Framework bootstrap
├── config/                     # Configuration files
├── database/
│   ├── factories/              # Model factories
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── docs/                       # Project documentation
├── public/                     # Public web assets
├── resources/
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript
│   └── views/                  # Blade templates
├── routes/
│   ├── console.php             # Artisan command routes
│   └── web/                    # Web routes (organized by feature)
├── storage/                    # Application storage
├── stubs/                      # Code generation templates
├── tests/
│   ├── Feature/                # Feature tests (HTTP, Jobs, Commands)
│   └── Unit/                   # Unit tests (Services, Helpers)
└── vendor/                     # Composer dependencies
```

## Features Directory

The `app/Features/` directory contains domain-organized business logic (Commands and Queries with their handlers).

### Structure

```
app/Features/
├── Changes/
│   └── Commands/              # Audit trail tracking commands
├── Events/
│   ├── Commands/              # Event management commands
│   └── Queries/               # Event data retrieval queries
├── Notices/
│   ├── Commands/              # Notice management commands
│   └── Queries/               # Notice data retrieval queries
├── Organizations/
│   ├── Commands/              # Organization management commands
│   └── Queries/               # Organization data retrieval queries
├── Reports/
│   └── Queries/               # Reporting queries
└── Troopers/
    ├── Commands/              # Trooper management commands
    └── Queries/               # Trooper data retrieval queries
```

### Naming Conventions

Each command or query follows a strict naming convention:

- **Command Class:** `{Verb}{Entity}Command` (e.g., `CreateEventCommand`, `UpdateTrooperCommand`)
- **Command Handler:** `{Verb}{Entity}CommandHandler` (implements `CommandHandlerInterface`)
- **Query Class:** `Get{Entity}{Context}Query` (e.g., `GetTroopersByRoleQuery`, `GetEventSummaryQuery`)
- **Query Handler:** `Get{Entity}{Context}QueryHandler` (implements `QueryHandlerInterface`)

### Examples

**Command:**
```php
// app/Features/Events/Commands/UpdateEventCommand.php
readonly class UpdateEventCommand
{
    public function __construct(
        public Event $event,
        public array $data
    ) {}
}

// app/Features/Events/Commands/UpdateEventCommandHandler.php
readonly class UpdateEventCommandHandler implements CommandHandlerInterface
{
    public function __invoke(UpdateEventCommand $command): Event
    {
        // Business logic here
    }
}
```

**Query:**
```php
// app/Features/Reports/Queries/GetEventSummaryQuery.php
readonly class GetEventSummaryQuery
{
    public function __construct(
        public int $lookback_days
    ) {}
}

// app/Features/Reports/Queries/GetEventSummaryQueryHandler.php
readonly class GetEventSummaryQueryHandler implements QueryHandlerInterface
{
    public function __invoke(GetEventSummaryQuery $query): array
    {
        // Data retrieval logic here
    }
}
```

## MagicBus Integration

Commands and Queries are dispatched through the **MagicBus** which automatically resolves handlers using naming conventions. See [CODING_CONVENTIONS.md](CODING_CONVENTIONS.md#magicbus-commandquery-pattern) for details.

## HTTP Controllers

The `app/Http/Controllers/` directory is organized by feature area:

```
app/Http/Controllers/
├── Account/               # User account management
├── Admin/                 # Administrative interfaces
│   ├── Awards/           # Award management
│   ├── Events/           # Event administration
│   ├── Notices/          # Notice management
│   ├── Organizations/    # Organization management
│   ├── Reports/          # Administrative reports
│   └── Troopers/         # Trooper management
├── Auth/                  # Authentication controllers
├── Dashboard/             # Dashboard widgets (HTMX)
├── Events/                # Public event controllers
├── Pickers/               # HTMX picker components
├── Widgets/               # Reusable HTMX widgets
├── HomeController.php     # Homepage
├── MagicBusController.php # Generic command/query dispatcher
└── ShareEventController.php # Event sharing
```

### Controller Pattern

Controllers follow the **Action-Domain-Responder (ADR)** pattern:

- **Invokable controllers** for single-action endpoints
- **Thin orchestration** - delegate to MagicBus commands/queries
- **No business logic** in controllers

## Models

The `app/Models/` directory contains Eloquent models organized into:

### Base Models (Auto-Generated)

Located in `app/Models/Base/`, these are **automatically generated** from the database schema using `php artisan code:models`. 

**DO NOT EDIT THESE FILES DIRECTLY** - they will be overwritten.

Base models include:
- All table columns as constants
- Fillable arrays
- Casts
- Relationships

### Extended Models

Located directly in `app/Models/`, these extend the base models to add:
- Custom methods
- Accessors/mutators
- Scopes
- Additional relationships
- Business logic helpers

Example:
```php
// app/Models/Trooper.php extends app/Models/Base/Trooper.php
class Trooper extends BaseTrooper
{
    use HasFactory;
    use HasTrooperStamps;
    
    // Custom scopes, methods, etc.
}
```

### Model Concerns

`app/Models/Concerns/` contains reusable traits:

- **HasTrooperStamps:** Tracks created_id, updated_id, deleted_id
- **HasFilter:** Adds filtering capabilities
- **HasAuditTrail:** Tracks model changes
- **HasObserver:** Registers model observers

### Model Scopes

`app/Models/Scopes/` contains query scope traits for specific models (e.g., `HasEventScopes`, `HasTrooperScopes`).

### Pivot Models

`app/Models/Pivots/` contains custom pivot models for many-to-many relationships with additional data or behavior.

## Enums

All enums are backed by strings and located in `app/Enums/`:

- **AchievementType:** Achievement categories
- **AwardFrequency:** How often awards are given
- **EventStatus:** Event lifecycle states
- **EventTrooperStatus:** Signup states
- **EventType:** Event categories
- **MembershipRole:** Trooper roles (member, moderator, administrator)
- **MembershipStatus:** Account states (pending, active, retired)
- **NoticeType:** Notice severity levels
- **NotificationFrequency:** Email preferences (never, instant, daily)
- **OrganizationType:** Organization hierarchy levels
- **TrooperTheme:** UI theme preferences

All enums use the `HasEnumHelpers` trait for common operations.

## Jobs

The `app/Jobs/` directory contains queueable jobs:

- **SendEventCreatedNotificationsJob:** Dispatched when events are published
- **SendEventCancelledNotificationsJob:** Dispatched when events are cancelled
- **SendTrooperRegisteredNotificationsJob:** Dispatched on new registrations
- **SendExceptionNotificationJob:** Error notification handling

Jobs follow the **Orchestration Pattern** - they delegate to Command/Query handlers rather than implementing business logic directly.

## Console Commands

The `app/Console/Commands/` directory contains Artisan commands:

- **CalculateTrooperAchievementsCommand:** Recalculates trooper stats and badges
- **CloseEventsCommand:** Auto-closes events after their end date
- **CloseEventShiftsCommand:** Auto-closes shifts after completion
- **GenerateFactoriesCommand:** Custom factory generation
- **SendDailyEventNotifications:** Sends daily event digest emails
- **SynchronizeOrganizations:** Syncs with external organization systems

## Policies

The `app/Policies/` directory contains authorization policies:

- **AwardPolicy:** Award management authorization
- **EventPolicy:** Event management authorization
- **NoticePolicy:** Notice management authorization
- **OrganizationPolicy:** Organization management authorization
- **TrooperPolicy:** Trooper management authorization

All policies use the `HasTrooperPermissionsTrait` for common permission checks.

## Rules

The `app/Rules/` directory contains custom validation rules organized by feature:

```
app/Rules/
├── Admin/
│   ├── Organizations/
│   │   ├── UniqueCostumeNameRule.php
│   │   └── UniqueNameRule.php
└── Auth/
    ├── AtLeastOneOrganizationSelectedRule.php
    └── UniqueOrganizationIdentifierRule.php
```

## Services

The `app/Services/` directory contains standalone service classes:

- **BreadCrumbService:** Navigation breadcrumb generation
- **FlashMessageService:** Flash message management
- **GeocodingService:** Address geocoding integration
- **GoogleService:** Google API integration
- **StandaloneService:** Base service class

## MagicBus

The `app/Bus/` directory contains the command/query bus implementation:

- **MagicBus.php:** Convention-based handler resolution and dispatch
- **Contracts/:** Handler interfaces (CommandHandlerInterface, QueryHandlerInterface)
- **Concerns/:** Handler behavior traits (ShouldBeTransactional, ShouldRunAfterResponse)

## Web Routes

Routes are organized by feature area in `routes/web/`:

```
routes/web/
├── account.php            # Account settings routes
├── admin.php              # Admin panel routes
├── auth.php               # Authentication routes
├── dashboard.php          # Dashboard routes
├── events.php             # Event routes
└── pickers.php            # HTMX picker routes
```

## Testing Structure

Tests mirror the application structure:

```
tests/
├── Feature/
│   ├── Http/Controllers/  # Controller tests (full HTTP cycle)
│   ├── Jobs/              # Job tests
│   └── Console/Commands/  # Command tests
└── Unit/
    ├── Features/          # Command/Query handler tests
    ├── Policies/          # Policy tests
    ├── Rules/             # Validation rule tests
    └── Services/          # Service class tests
```

## Code Generation

The project uses:
- **Reliese Laravel:** Auto-generates base models from database schema
- **Custom Factory Generator:** Creates factories matching base models
- **Laravel scaffolding:** Standard make commands for controllers, models, etc.

## Configuration Files

Key configuration files in `config/`:

- **tracker.php:** Application-specific configuration
- **models.php:** Reliese model generation configuration
- **services.php:** Third-party service credentials
- **share.php:** Social sharing configuration

## Additional Resources

- **Architecture Patterns:** [CODING_CONVENTIONS.md](CODING_CONVENTIONS.md)
- **Database Schema:** [DATABASE.md](DATABASE.md)
- **Authentication Flow:** [AUTHENTICATION.md](AUTHENTICATION.md)
- **Notifications:** [NOTIFICATIONS.md](NOTIFICATIONS.md)
