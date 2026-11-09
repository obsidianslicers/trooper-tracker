# Coding Conventions

## 1. Introduction

This document outlines the coding standards, conventions, and architectural patterns for the Troop Tracker project. Adhering to these guidelines is crucial for ensuring code consistency, readability, and long-term maintainability as we incrementally refactor the application.

## 2. General Principles

Our development philosophy is guided by a few core tenets:

- **Simplicity and Readability:** *Simple is better than complex*, and *readability counts*. Write code that is explicit, straightforward, and easy for others to understand. If an implementation is hard to explain, it's likely a bad idea.
- **Follow The "Obvious" Way:** *There should be one—and preferably only one—obvious way to do it.* When a specific rule is not defined here, default to the standard conventions and best practices of the Laravel framework.
- **Pragmatic Refactoring:** We follow the Strangler Fig pattern for modernization. This embraces the idea that *now is better than never*, allowing us to make steady, incremental progress. However, we also recognize that *never is often better than right now*, which is why we plan our refactoring with patterns like [ADR](#6-architecture) instead of rushing a full rewrite.
- **Handle Errors Explicitly:** *Errors should never pass silently.* All new and refactored code should include robust error handling and validation.
- **Embrace SOLID:** Our architectural choices are guided by the [SOLID principles](https://en.wikipedia.org/wiki/SOLID) to create maintainable and scalable code. You will see these principles reflected in the patterns below.

## 3. Naming Conventions

We enforce a strict set of casing rules to maintain consistency across the codebase.

| Element                                       | Convention   | Example                               |
| --------------------------------------------- | ------------ | ------------------------------------- |
| Classes (Controllers, Models, Services, etc.) | `PascalCase` | `LoginSubmitController`, `Trooper`    |
| Functions & Class Methods                     | `camelCase`  | `function handleRequest(Request $data)` |
| Function/Method Parameters                    | `snake_case` | `function myMethod(string $user_name)` |
| Test Function/Method Parameters               | `snake_case` | `function test_my_feature()` |
| Local Variables                               | `snake_case` | `$local_variable = 'value';`          |
| Class Properties/Variables                    | `snake_case` | `private string $class_property;`     |

## 4. Coding Style

### 4.1. Function & Method Signatures

All function and method signatures **must** include explicit, scalar type hints for parameters and return types wherever possible.

```php
public function findUserById(int $user_id): ?User
{
    // ...
}
```

### 4.2. Line and Function Length

- **Line Length:** Aim to keep lines of code under **100 characters** for better readability.
- **Function/Method Length:** Functions and methods should be focused and concise, ideally not exceeding **30 lines**. If a method grows beyond this, consider refactoring it into smaller, private helper methods.

## 5. Database Conventions

To leverage Laravel's Eloquent ORM conventions and simplify relationship definitions, all database schema elements **must** follow these naming rules. Adhering to these conventions allows Eloquent to automatically infer relationships without requiring explicit key definitions in your models.

| Element | Convention | Example |
| :--- | :--- | :--- |
| **Table Names** | Plural, `snake_case` | `troopers`, `event_troopers` |
| **Column Names** | `snake_case` | `first_name`, `event_date` |
| **Boolean Column Names** | `is_`, `can_`, `has_`, `should_` | `is_verified`, `can_allow`, `has_limits` |
| **Primary Key** | `id` | An auto-incrementing integer named `id`. |
| **Foreign Keys** | Singular table name + `_id` | A `posts` table has a `user_id` column to link to the `users` table. |
| **Pivot Tables** | Singular table names, alphabetized, joined by `_` | `role_user` for a `roles` and `users` relationship. |
| **Timestamps** | `created_at`, `updated_at` | For Eloquent's automatic timestamping. |
| **Soft Deletes** | `deleted_at` | For Eloquent's `SoftDeletes` trait. |

## 6. Architecture

This project follows **Action-Domain-Responder (ADR)** with **Command/Query Separation** via
**Hyperdrive messages**. Controllers orchestrate; messages hold the business logic.

**See [ARCHITECTURE.md](ARCHITECTURE.md) for comprehensive architectural documentation.**

Key architectural standards:
- Use invokable controllers (single-action)
- Put domain logic in messages under `app/Messages/` and call them with `::call()`
- Keep messages focused and testable
- Follow SOLID principles

## 7. Controllers

### 7.1. Invokable (Single-Action) Controllers

For controller actions that perform a single, specific task (e.g., submitting a form, displaying a page), prefer using invokable (single-action) controllers. This aligns with the Single Responsibility Principle and keeps our routing and controller logic clean and focused.

**Example:**

```php
// app/Http/Controllers/LoginSubmitController.php
class LoginSubmitController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        // Handle login submission...
    }
}

// routes/web.php
Route::post('/login', LoginSubmitController::class);
```

## 8. Jobs & Console Commands

### 8.1. Orchestration Pattern

Jobs and Console Commands follow the **Orchestration Pattern**: they are thin orchestrators that
call messages. Business logic stays in the messages, where it can be reused and tested in
isolation.

**Key Principles:**

-   **Jobs and Commands are Orchestrators:** They coordinate a workflow by calling messages, not
    by implementing business logic.
-   **Business Logic Lives in Messages:** Domain logic belongs in `app/Messages/`.
-   **Reusability:** A message can be called from Controllers, Jobs, Console Commands, or other
    messages.
-   **Testability:** Messages have no dependency on Laravel's job/command infrastructure, so they
    are easy to unit test.

**Example:**

```php
// app/Jobs/SendEventCreatedNotificationsJob.php
class SendEventCreatedNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Event $event
    ) {}

    public function handle(): void
    {
        $troopers = GetTroopersForEventCreated::call(event: $this->event);

        foreach ($troopers as $trooper)
        {
            SendEventCreatedNotification::call(event: $this->event, trooper: $trooper);
        }
    }
}

// app/Console/Commands/SendDailyEventNotifications.php
class SendDailyEventNotifications extends Command
{
    protected $signature = 'tracker:send-daily-event-notifications';
    protected $description = 'Send daily event digest emails to troopers';

    public function handle(): int
    {
        $troopers = GetTroopersForDailyEventNotifications::call();

        foreach ($troopers as $trooper)
        {
            SendEventDailyNotification::call(trooper: $trooper);
        }

        $this->info("Sent {$troopers->count()} daily digests.");

        return Command::SUCCESS;
    }
}
```

**Benefits:**

-   Jobs and Commands remain simple, focused, and easy to understand
-   Business logic is centralized in messages for consistency
-   Messages can be reused across entry points (web, CLI, queue)
-   Unit testing is simplified by testing messages independently
-   Aligns with the Single Responsibility Principle

## 9. Hyperdrive Messages

Domain logic lives in **Hyperdrive messages**: single classes in `app/Messages/` that extend
`Hyperdrive\Message`. The constructor is the message's input contract and `handle()` is the
operation. There is no separate handler class. See [ARCHITECTURE.md](ARCHITECTURE.md) for the
overview and the [Hyperdrive README](../tracker-app/packages/hyperdrive/README.md) for how
parameters are hydrated.

### 9.1. Layout and Naming

```
app/Messages/[Feature]/
├── Commands/      # write operations (state change)
├── Queries/       # read operations (no side effects)
├── PageData/      # page payloads that aggregate queries for one page
└── Resources/     # response shapes, where needed
```

Shared traits live in `app/Messages/Concerns/`. Large features may group messages in a
subfolder by topic (e.g. `Troopers/Commands/Membership/`).

A message is named `[Verb] [Object] [optional details]` with **no** `Command`/`Query` suffix;
the folder already says which it is.

| Type | Example |
|------|---------|
| Command | `App\Messages\Events\Commands\UpdateEvent` |
| Command | `App\Messages\Troopers\Commands\VoidTrooper` |
| Query | `App\Messages\Troopers\Queries\GetTroopersByRole` |
| Query | `App\Messages\Reports\Queries\GetEventSummary` |
| Page data | `App\Messages\Auth\PageData\LoginPageData` |

### 9.2. Writing a Message

```php
// app/Messages/Troopers/Queries/GetTroopersByRole.php
/**
 * @method static Collection<int, Trooper> call(MembershipRole $membership_role)
 */
final class GetTroopersByRole extends Message
{
    public function __construct(
        private readonly MembershipRole $membership_role,
    ) {}

    public function handle(): Collection
    {
        return Trooper::active()
            ->where(Trooper::MEMBERSHIP_ROLE, $this->membership_role)
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();
    }
}
```

- Make the class `final`, with `private readonly` constructor properties.
- Keep the `@method static ... call(...)` PHPDoc in sync with the constructor so `::call()`
  keeps autocomplete.
- Inject services into `handle()` (e.g. `handle(XenforoService $xenforo)`), not the constructor;
  the constructor is hydrated from message input only.
- For the signed-in user, type a constructor parameter as `Hyperdrive\Contracts\Actor $actor`;
  this keeps the session user distinct from target records looked up by id.

### 9.3. Calling a Message

- **From a page or form request:** `IndexPageData::call($request)` hydrates constructor
  parameters from the validated request, route parameters and the authenticated actor.
- **Everywhere else:** use named arguments, e.g.
  `GetEventSummary::call(moderator: $trooper, lookback: 30)`. Named arguments keep the
  dependencies obvious and refactoring safe.
- Messages can call other messages. Hyperdrive throws if a call chain loops back on itself.

### 9.4. Transactions

Use the `ShouldBeTransactional` trait to run `handle()` inside a database transaction:

```php
use Hyperdrive\Concerns\ShouldBeTransactional;

final class VoidTrooper extends Message
{
    use ShouldBeTransactional;
}
```

Work that should not block the HTTP response belongs in a queued Job that calls the message.

### 9.5. Testing Messages

Construct the message and call `handle()` directly:

```php
public function test_handle_returns_troopers_with_role(): void
{
    $admin = Trooper::factory()->asSystemOwner()->create();

    $subject = new GetTroopersByRole(MembershipRole::SYSTEM_OWNER);

    $result = $subject->handle();

    $this->assertTrue($result->contains($admin));
}
```

When a controller, job or console test needs to stub a message, use the `FakesMessages` test
trait. Messages you don't fake still run for real:

```php
use Tests\Concerns\FakesMessages;

$messages = $this->fakeMessages([GlobalSearch::class => $payload]);

$response = $this->actingAs($trooper)->get(route('search.all', ['q' => 'Vader']));

$messages->assertCalled(GlobalSearch::class, fn (array $params): bool => $params['term'] === 'Vader');
```

### 9.6. Best Practices

**DO:**
- ✅ Keep one operation per message
- ✅ Use descriptive, verb-first names (`CreateEvent`, not `EventCreator`)
- ✅ Return typed results (`void` for commands that return nothing)
- ✅ Validate input in form requests before calling a message

**DON'T:**
- ❌ Access HTTP context (Request, Session) inside `handle()`
- ❌ Overload a Command or Query to also build multi-query page data (use a PageData message)
- ❌ Mix a write and a read in the same message

### 9.7. Integration with ADR

- **Action (Controller):** Validates input and calls a message
- **Domain (Message):** Runs the business logic and returns a result
- **Responder:** Controller turns the result into an HTTP response

## 10. Testing (PHPUnit)

A robust test suite is essential for our refactoring efforts. All new features and refactored code must be accompanied by tests.

### 10.1. Test Strategy

The appropriate test type depends on the component being tested:

-   **Controllers:** Must be covered by **feature tests**. Feature tests exercise the full HTTP request/response cycle, ensuring that routing, middleware, request validation, and response rendering work correctly together.
-   **Jobs:** Must be covered by **feature tests**. Feature tests verify that jobs correctly orchestrate message calls and handle queue-specific concerns like failures and retries.
-   **Console Commands:** Must be covered by **feature tests**. Feature tests confirm that commands parse arguments correctly, orchestrate message calls, and produce the expected console output.
-   **Messages:** Should be covered by **unit tests** in `tests/Unit/Messages/`, mirroring `app/Messages/`.
-   **Services:** Should be covered by **unit tests**. Services are decoupled from framework infrastructure, so they are ideal candidates for fast, focused unit tests.

This strategy ensures that our orchestration layers (Controllers, Jobs, Commands) are tested in realistic scenarios, while our business logic (Messages and Services) receives fast, isolated unit test coverage.

### 10.2. Test Method Naming

All test method names must be `snake_cased` and begin with the `test_` prefix. The name should clearly describe what the test is asserting.

```php
public function test_invoke_handles_unapproved_user(): void
{
    // ...
}
```

### 10.3. Subject Under Test

When instantiating the class being tested, the variable name **must** be `$subject`.

```php
public function test_something(): void
{
    $subject = new GetTroopersByRole(MembershipRole::SYSTEM_OWNER);
    $result = $subject->handle();
    $this->assertNotEmpty($result);
}
```

### 10.4. Mocking

When creating mocks, use **Mockery** with the `shouldReceive()` chain for setting up expectations. This provides a clear, readable format.

```php
// Example for a mock of Illuminate\Session\Store
$session_mock = Mockery::mock(Store::class);

$session_mock->shouldReceive('get')
    ->once()
    ->with('flash_messages', [])
    ->andReturn([]);
```

## 11. Component Reference

This section provides an overview of key components in the application.

### 11.1. Policies

Authorization policies control who can perform actions on resources. All policies use the `HasTrooperPermissionsTrait` for common permission checks.

**Available Policies:**
- **AwardPolicy:** Controls award creation and management (admins/moderators only)
- **EventPolicy:** Controls event creation and management (admins/moderators only)
- **NoticePolicy:** Controls notice creation and management (admins/moderators only)
- **OrganizationPolicy:** Controls organization updates (admins and scoped moderators)
- **TrooperPolicy:** Controls trooper profile viewing and editing (admins and scoped moderators)

**Common Permission Methods:**
```php
// From HasTrooperPermissionsTrait
protected function isAdministrator(Trooper $trooper): bool;
protected function isModerator(Trooper $trooper): bool;
```

**Usage in Controllers:**
```php
$this->authorize('update', $event);  // Calls EventPolicy::update()
```

### 11.2. Validation Rules

Custom validation rules provide reusable validation logic organized by feature area.

**Auth Rules:**
- **AtLeastOneOrganizationSelectedRule:** Ensures at least one organization is selected from array
- **UniqueOrganizationIdentifierRule:** Validates organization-specific member IDs are unique

**Admin/Organizations Rules:**
- **UniqueCostumeNameRule:** Ensures costume names are unique within an organization
- **UniqueNameRule:** Ensures organization names are unique among siblings

**Admin/Troopers Rules:**
- **ValidTrooperEmailRule:** Requires a valid email format, except when the value is unchanged
  from the trooper's current stored email (grandfathers legacy placeholder emails)

**Usage in Requests:**
```php
public function rules(): array
{
    return [
        'identifier' => ['required', new UniqueOrganizationIdentifierRule($organization, $trooper)],
    ];
}
```

### 11.3. Services

Standalone service classes provide reusable functionality across the application.

**Available Services:**
- **BreadCrumbService:** Manages navigation breadcrumbs
  - `add(string $title, ?string $url = null): void`
  - `get(): Collection`
  
- **GeocodingService:** Geocodes addresses to lat/long coordinates
  - `geocode(string $address): ?array`
  
- **GoogleService:** Geocodes addresses with the Google Maps API
  - OAuth authentication
  - API access management

### 11.4. Console Commands

Custom Artisan commands automate maintenance tasks and scheduled operations.

**Event Management:**
- **CloseEventsCommand** (`tracker:close-events`)
  - Auto-closes events after their end date
  - Scheduled to run daily
  
- **CloseEventShiftsCommand** (`tracker:close-event-shifts`)
  - Auto-closes shifts after completion
  - Scheduled to run hourly

**Notifications:**
- **SendDailyEventNotifications** (`tracker:send-daily-event-notifications`)
  - Sends daily event digest emails
  - Scheduled to run daily at configured time

**Trooper Management:**
- **CalculateTrooperAchievementsCommand** (`tracker:calculate-trooper-achievements`)
  - Recalculates trooper stats and badges
  - Can be run manually or scheduled

**Synchronization:**
- **SynchronizeOrganizations** (`tracker:synchronize-organizations`)
  - Syncs organization data from external systems
  - Scheduled based on integration needs

**Development:**
- **GenerateFactoriesCommand** (`fabricator:generate-factories`)
  - Generates factory classes from base models
  - Run after database schema changes

### 11.5. Model Traits

Reusable traits extend model functionality.

**Model Concerns:**
- **HasTrooperStamps:** Tracks created_id, updated_id, deleted_id
  - Automatically sets trooper ID on create/update/delete
  - Provides relationships to trooper who performed actions
  
- **HasFilter:** Adds filtering capabilities to models
  - Enables query building from request parameters
  
- **HasAuditTrail:** Tracks field-level changes
  - Creates `ModelChange` records for auditing
  - Logs old and new values
  
- **HasObserver:** Registers model observers
  - Centralizes model event handling

**Model Scopes (Query Builders):**
- **HasEventScopes:** Event-specific query scopes
- **HasTrooperScopes:** Trooper-specific query scopes
- **HasOrganizationScopes:** Organization-specific query scopes
- **HasAwardScopes:** Award-specific query scopes

**Usage:**
```php
class Event extends BaseEvent
{
    use HasTrooperStamps;
    use HasAuditTrail;
    use HasEventScopes;
}

// Trooper stamps automatically set
$event = Event::create([...]);  
// $event->created_id now contains Auth::id()

// Audit trail automatically tracks changes
$event->update(['name' => 'New Name']);
// ModelChange record created with old/new values
```

### 11.6. Enums

All enums are backed by strings and use the `HasEnumHelpers` trait.

**Available Enums:**
- **AchievementType:** Achievement categories
- **AwardFrequency:** `once`, `monthly`, `yearly`
- **EventStatus:** `draft`, `open`, `closed`, `cancelled`
- **EventTrooperStatus:** `none`, `going`, `tentative`, `unavailable`
- **MembershipRole:** `member`, `handler`, `visitor`, `event_coordinator`, `moderator`, `staff_officer`, `system_owner`
- **MembershipStatus:** `pending`, `active`, `retired`
- **NoticeType:** `info`, `warning`, `alert`
- **NotificationFrequency:** `never`, `instant`, `daily`
- **OrganizationType:** `club`, `garrison`, `squad`
- **TrooperTheme:** UI theme preferences

**Enum Helper Methods (from HasEnumHelpers):**
```php
// Get array of value => label
$options = MembershipRole::toArray();
// ['member' => 'Member', 'moderator' => 'Moderator', ...]

// Get comma-delimited string for validation
$rule = 'in:' . EventStatus::toValidator();
// 'in:draft,open,closed,cancelled'
```

### 11.7. Mail Classes

Mailable classes for email notifications.

**Event Notifications:**
- **InstantEventNotification:** Single event notification email
- **DailyEventNotification:** Daily digest of multiple events
- **CancelledEventNotification:** Event cancellation notice

**Exception Handling:**
- **ExceptionOccurred:** Sends exception details to admins

**Usage:**
```php
Mail::to($trooper->email)->queue(new InstantEventNotification($event));
```