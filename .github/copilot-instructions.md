# GitHub Copilot Instructions for Troop Tracker

This document provides instructions and context for AI coding agents to enhance their performance and align with project standards within this Laravel application.


## Critical Rules (Copilot must follow these)
* The authenticated entity is Trooper, never User.
* All files must begin with declare(strict_types=1);.
* All methods require explicit scalar type hints.
* Controllers follow ADR and must remain thin.
* Business logic belongs in Services, not Controllers, Jobs, or Commands.
* Base models in Models/Base must never be edited.
* Use model constants for column names.
* Use domain vocabulary: Trooper, Troop, Costume, Organization, Notice.
* Tests must be behavior‑driven, not implementation‑driven.

## Code Generation Rules

### Controllers (Actions)
* Must be invokable classes.
* Must orchestrate services, not contain logic.
* Must not reference User.
* Must not perform database queries directly unless trivial.

### Services (Domain)
* Contain all business logic.
* Must be pure, testable, and independent of HTTP context.
* Should accept typed parameters and return typed results.

### Jobs & Commands (Orchestration)
* Must call services.
* Must not contain business logic.

### Models
* Extend auto‑generated base models.
* Add scopes, accessors, helpers only in extended models.
* Use constants for column names.

### Naming & Style Rules
* Classes: PascalCase
* Methods: camelCase
* Parameters & variables: snake_case
* Tests: test_ prefix + snake_case
* Max line length: 100 chars
* Max method length: 30 lines

## Project Overview

**Troop Tracker** is a Laravel application designed for members of the **501st Legion** and other **Star Wars costuming clubs**.

### Key Architectural Principle

## Project Purpose

This application exists to support costuming club members by providing tools to:

- Track upcoming and past **troops** (events)
- Manage **trooper signups**, approvals, and attendance
- Coordinate event details across hierarchical organizations (Clubs → Garrisons → Squads, coded as Organizations → Regions → Units)
- Handle event notifications based on trooper preferences (instant, daily, never)
- Manage event uploads/photos with trooper tagging

All domain language, models, factories, and tests must reflect this Star Wars costuming context.

## Tech Stack

- **Laravel:** 12.x (latest features enabled)
- **PHP:** 8.2+ (strict types, scalar type hints required)
- **Database:** MySQL with auto-generated base models
- **Frontend:** Blade templates, Bootstrap 5.2x, HTMX 2.x, Alpine.js
- **Testing:** PHPUnit (SQLite in-memory for tests)

## Architecture Patterns

### Action-Domain-Responder (ADR)

All new and refactored code follows the ADR pattern:

- **Action (Controller):** Invokable controllers that orchestrate domain logic
  - Example: `LoginSubmitController`, `RegisterSubmitController`
  - Use single-action controllers: `Route::post('/login', LoginSubmitController::class)`
  - Controllers are thin orchestrators, not business logic containers

- **Domain (Services):** Business logic lives in Service classes, independent of web context, commands and queries are executed as messages thru the MagicBus
  - Located in `app/Services/`
  - Examples: `FlashMessageService`, `BreadCrumbService`, event notification services
  - Service classes are reusable across Controllers, Jobs, and Commands

- **Responder:** Blade views, JSON responses, or redirects
  - Located in `resources/views/`
  - Uses Bootstrap 5 + HTMX for dynamic interactions

### Orchestration Pattern for Jobs & Commands

Jobs and Commands follow the **Orchestration Pattern** - they coordinate Service class calls, not implement business logic:

```php
// ✅ GOOD: Job orchestrates service
class SendEventCreatedNotificationsJob implements ShouldQueue
{
    public function handle(SendEventNotificationsCommand $send_event_notifications): void
    {
        $send_event_notifications($this->event);
    }
}

// ❌ BAD: Business logic in job
class SendEventCreatedNotificationsJob
{
    public function handle(): void
    {
        $troopers = Trooper::active()->get(); // Business logic here!
        // ...
    }
}
```

## Database & Model Architecture

### Auto-Generated Base Models

This project uses **reliese/laravel** to auto-generate base models from the database schema:

- **Base Models:** `app/Models/Base/` (auto-generated, DO NOT EDIT)
  - Contains all table columns, relationships, and fillable arrays
  - Generated via `php artisan code:models`

- **Extended Models:** `app/Models/` (your code here)
  - Extend base models to add custom methods, accessors, scopes
  - Example: `Trooper extends Base\Trooper`

### Database Naming Conventions

Strict conventions enable Eloquent auto-inference:

| Element | Convention | Example |
|---------|-----------|---------|
| Tables | Plural `snake_case` | `troopers`, `event_troopers` |
| Columns | `snake_case` | `first_name`, `event_date` |
| Booleans | `is_`, `can_`, `has_` prefix | `is_verified`, `has_limits` |
| Primary Key | `id` | Auto-incrementing integer |
| Foreign Keys | Singular table + `_id` | `trooper_id`, `event_id` |
| Pivot Tables | Alphabetized singular names | `event_trooper` |
| Timestamps | `created_at`, `updated_at` | Laravel standard |

### Model Constants for Column Names

All models use constants for column references (type-safe):

```php
// In Trooper model
public const EMAIL = 'email';
public const MEMBERSHIP_STATUS = 'membership_status';

// Usage
$trooper->{Trooper::EMAIL} = 'test@example.com';
Trooper::factory()->state([Trooper::EMAIL => 'custom@example.com']);
```

## Coding Conventions

### Naming Standards

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | `PascalCase` | `LoginSubmitController`, `FlashMessageService` |
| Methods | `camelCase` | `handleRequest()`, `sendNotifications()` |
| Parameters | `snake_case` | `function findById(int $user_id)` |
| Variables | `snake_case` | `$local_variable`, `$event_date` |
| Test Methods | `snake_case` with `test_` prefix | `test_invoke_handles_unapproved_user()` |

**Note**: Laravel relationship methods within the TroopTracker follow the `snake_case` convention (e.g. `trooper_events()`), vs the well known Eloquent convention of using `camelCase`.

### Type Hints

**REQUIRED:** All function/method signatures must include explicit scalar type hints:

```php
// ✅ GOOD
public function findUserById(int $user_id): ?Trooper
{
    return Trooper::find($user_id);
}

// ❌ BAD - missing type hints
public function findUserById($user_id)
{
    return Trooper::find($user_id);
}
```

### Code Quality Rules

- **Line Length:** ≤100 characters
- **Method Length:** ≤30 lines (extract private helpers if longer)
- **Error Handling:** Errors should never pass silently - explicit validation required
- **Strict Types:** Every file must start with `declare(strict_types=1);`

## Domain Vocabulary

### Critical Terminology

Use correct domain language throughout the codebase:

- **Trooper** (not User) - authenticated member of the organization
- **Troop** (not Event) - a costuming event/appearance
- **Costume** - approved Star Wars costume owned by a trooper
- **Organization** - costuming club/garrison/squad
- **Notice** - internal messaging/notification system

### Trooper Authentication

```php
// ✅ CORRECT usage
$trooper = Trooper::factory()->asActive()->create();
$this->actingAs($trooper);
Auth::user(); // Returns a Trooper instance

// ❌ NEVER use these
$user = User::factory()->create(); // User model doesn't exist!
```

### Factory States

Factories include domain-specific states:

```php
// Trooper states
Trooper::factory()->asActive()->create();
Trooper::factory()->asPending()->create();
Trooper::factory()->asRetired()->create();
Trooper::factory()->asAdministrator()->create();
Trooper::factory()->asModerator()->create();
Trooper::factory()->withPassword('secret')->create();

// Event states (refer to EventFactory for available states)
Event::factory()->upcoming()->create();
Event::factory()->past()->create();
```

## Testing Strategy

### Test Types by Component

| Component | Test Type | Why |
|-----------|-----------|-----|
| Controllers | Feature | Full HTTP request/response cycle |
| Jobs | Feature | Queue-specific concerns + orchestration |
| Commands | Feature | Argument parsing + console output |
| Services | Unit | Fast, isolated business logic tests |

### Non-Brittle, Behavior-Driven Tests

Focus on **what the Trooper experiences**, not implementation:

```php
// ✅ GOOD - tests behavior
public function test_login_redirects_approved_trooper_to_dashboard(): void
{
    $trooper = Trooper::factory()->asActive()->withPassword('password')->create();
    
    $response = $this->post('/login', [
        'email' => $trooper->email,
        'password' => 'password',
    ]);
    
    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($trooper);
}

// ❌ BAD - brittle implementation testing
public function test_login_submit_controller_calls_auth_attempt(): void
{
    // Testing controller method names, internal structure
}
```

### Avoid Brittle Assertions

**DO NOT** assert:
- Exact HTML markup or CSS classes
- DOM structure or element counts
- Specific database column names
- Controller/view file names

**DO** assert:
- `assertSeeText()` - visible content
- `assertRedirect()` - navigation behavior
- `assertSessionHas()` / `assertSessionMissing()`
- High-level JSON structure
- Database state via model attributes

### Test Conventions

```php
// Variable naming
public function test_something(): void
{
    $subject = new ServiceClass(); // Class under test is always $subject
    $result = $subject->doSomething();
    $this->assertTrue($result);
}

// Factory helpers over manual attribute setting
// ✅ Add to factory
public function withEmail(string $email): static
{
    return $this->state([Trooper::EMAIL => $email]);
}

// ❌ Don't set manually in tests
$trooper = Trooper::factory()->create();
$trooper->email = 'test@example.com';
$trooper->save();
```

## Development Workflows

### Running Tests

```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# With coverage (configured for SQLite in-memory)
php artisan test --coverage
```

### Database & Migrations

```bash
# Run migrations
php artisan migrate

# Rollback
php artisan migrate:rollback

# Fresh + seed
php artisan migrate:fresh --seed

# Generate base models after schema changes
php artisan code:models
```

### Frontend Development

```bash
# Development (watch mode)
npm run dev

# Production build
npm run build
```

### Key Artisan Commands

```bash
# Generate factory for a model
php artisan make:factory TrooperFactory

# Generate invokable controller
php artisan make:controller LoginSubmitController --invokable

# Run specific job
php artisan queue:work

# Code quality
./vendor/bin/pint  # Laravel Pint formatter
```

## Key Files & Directories

### Critical Reference Files

- **[CODING_CONVENTIONS.md](../CODING_CONVENTIONS.md)** - Detailed conventions and architectural patterns
- **[AUTHENTICATION.md](../AUTHENTICATION.md)** - Multi-provider auth flow (Email, Google, XenForo OAuth)
- **[NOTIFICATIONS.md](../NOTIFICATIONS.md)** - Event notification system architecture

### Directory Structure

```
tracker-app/
├── app/
│   ├── Http/Controllers/     # Invokable ADR Action controllers
│   ├── Services/             # Domain business logic
│   ├── Models/               # Extended Eloquent models
│   │   └── Base/            # Auto-generated (DO NOT EDIT)
│   ├── Enums/               # Backed enums with HasEnumHelpers trait
│   ├── Jobs/                # Queue jobs (orchestrators)
│   └── Console/Commands/    # Artisan commands (orchestrators)
├── database/
│   ├── factories/           # Model factories with domain states
│   └── migrations/          # Schema migrations
├── resources/views/         # Blade templates (Bootstrap + HTMX)
├── routes/web/              # Organized route files by feature
└── tests/
    ├── Feature/             # HTTP, Job, Command tests
    └── Unit/                # Service, helper tests
```

## Enums with Helper Trait

All enums use `HasEnumHelpers` trait for common operations:

```php
enum MembershipStatus: string
{
    use HasEnumHelpers;
    
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case RETIRED = 'retired';
}

// Usage
MembershipStatus::toArray();      // ['pending' => 'Pending', ...]
MembershipStatus::toValidator();  // 'pending,active,retired'
```

## Common Pitfalls

1. **Don't edit Base models** - they're auto-generated and will be overwritten
2. **Always use Trooper, never User** - the User model doesn't exist
3. **Type hint everything** - strict types are enforced project-wide
4. **Keep controllers thin** - business logic belongs in Services
5. **Use factory states** - add helper methods to factories instead of inline state
6. **Test behavior, not structure** - avoid brittle assertions on markup/DOM

## Additional Resources

See the following documents for deeper dives:

- Architecture patterns: [CODING_CONVENTIONS.md](../CODING_CONVENTIONS.md)
- Auth flows: [AUTHENTICATION.md](../AUTHENTICATION.md)
- Notification system: [NOTIFICATIONS.md](../NOTIFICATIONS.md)
