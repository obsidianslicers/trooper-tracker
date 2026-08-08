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

This project follows **Action-Domain-Responder (ADR)** with **Command/Query Separation** via **MagicBus**. Controllers orchestrate, handlers execute business logic.

**See [ARCHITECTURE.md](ARCHITECTURE.md) for comprehensive architectural documentation.**

Key architectural standards:
- Use invokable controllers (single-action)
- Dispatch Commands/Queries through MagicBus
- Keep handlers focused and testable
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

Jobs and Console Commands should follow the **Orchestration Pattern**, acting as thin orchestrators that dispatch Commands and Queries through the MagicBus. This keeps the business logic in the Domain layer (Command/Query Handlers) where it can be reused and tested in isolation.

**Key Principles:**

-   **Jobs and Commands are Orchestrators:** They should coordinate workflow by dispatching Commands/Queries, not implement business logic.
-   **Business Logic Lives in Handlers:** Extract all domain logic into dedicated Command and Query Handlers in `app/Features/`.
-   **Reusability:** Handlers can be called from Controllers, Jobs, Console Commands, or other Handlers via MagicBus.
-   **Testability:** Handlers are easier to unit test because they have no dependencies on Laravel's job/command infrastructure.

**Example:**

```php
// app/Jobs/SendEventCreatedNotificationsJob.php
class SendEventCreatedNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Event $event
    ) {
    }

    /**
     * Execute the job by dispatching commands via MagicBus.
     */
    public function handle(MagicBus $bus): void
    {
        // Orchestrate - dispatch command through MagicBus
        $bus->send(new SendEventCreatedNotificationCommand($this->event));
    }
}

// app/Console/Commands/SendDailyEventNotifications.php
class SendDailyEventNotifications extends Command
{
    protected $signature = 'tracker:send-daily-event-notifications';
    protected $description = 'Send daily event digest emails to troopers';

    /**
     * Execute the console command by dispatching queries/commands via MagicBus.
     */
    public function handle(MagicBus $bus): int
    {
        // Orchestrate - get eligible troopers via query
        $troopers = $bus->send(new GetTroopersForDailyEventNotificationsQuery());
        
        // Dispatch command for each trooper
        foreach ($troopers as $trooper) {
            $bus->send(new SendEventDailyNotificationCommand($trooper));
        }
        
        $this->info("Sent {$troopers->count()} daily digests.");
        
        return Command::SUCCESS;
    }
}

// app/Features/Events/Commands/SendEventCreatedNotificationCommandHandler.php
readonly class SendEventCreatedNotificationCommandHandler implements CommandHandlerInterface
{
    /**
     * Business logic: Create notification records and queue emails.
     */
    public function __invoke(SendEventCreatedNotificationCommand $command): void
    {
        $troopers = $this->getEligibleTroopers($command->event);
        
        foreach ($troopers as $trooper) {
            $this->createNotificationAndQueueEmail($command->event, $trooper);
        }
    }
    
    // Additional private helper methods...
}
```

**Benefits:**

-   Jobs and Commands remain simple, focused, and easy to understand
-   Business logic is centralized in Handlers for consistency
-   Handlers can be reused across different entry points (web, CLI, queue)
-   Unit testing is simplified by testing Handlers independently
-   Aligns with the Single Responsibility Principle
-   Maintains consistency with the MagicBus Command/Query pattern used in Controllers

## 9. MagicBus: Command/Query Pattern

This project uses **MagicBus** for Command/Query Separation. See [ARCHITECTURE.md](ARCHITECTURE.md) for conceptual overview.

### 9.1. Naming Convention

MagicBus auto-resolves handlers by convention:

| Type | Message Class | Handler Class |
|------|---------------|---------------|
| Command | `CreateEventCommand` | `CreateEventCommandHandler` |
| Command | `UpdateTrooperCommand` | `UpdateTrooperCommandHandler` |
| Query | `GetTroopersByRoleQuery` | `GetTroopersByRoleQueryHandler` |
| Query | `GetEventSummaryQuery` | `GetEventSummaryQueryHandler` |

**Rule:** `{MessageClass} + "Handler" = {HandlerClass}`

### 9.2. Creating Commands

Commands represent **write operations** that change state.

**Command Class (Message):**
```php
// app/Features/Events/Commands/CreateEventCommand.php
readonly class CreateEventCommand
{
    public function __construct(
        public Organization $organization,
        public array $data
    ) {}
}
```

**Command Handler:**
```php
// app/Features/Events/Commands/CreateEventCommandHandler.php
readonly class CreateEventCommandHandler implements CommandHandlerInterface
{
    public function __invoke(CreateEventCommand $command): Event
    {
        $event = Event::create([
            Event::ORGANIZATION_ID => $command->organization->id,
            Event::NAME => $command->data['name'],
            Event::EVENT_DATE => $command->data['event_date'],
            // ... other fields
        ]);
        
        return $event;
    }
}
```

### 9.3. Creating Queries

Queries represent **read operations** that fetch data without changing state.

**Query Class (Message):**
```php
// app/Features/Troopers/Queries/GetTroopersByRoleQuery.php
readonly class GetTroopersByRoleQuery
{
    public function __construct(
        public MembershipRole $role
    ) {}
}
```

**Query Handler:**
```php
// app/Features/Troopers/Queries/GetTroopersByRoleQueryHandler.php
readonly class GetTroopersByRoleQueryHandler implements QueryHandlerInterface
{
    public function __invoke(GetTroopersByRoleQuery $query): Collection
    {
        return Trooper::where(Trooper::MEMBERSHIP_ROLE, $query->role->value)
            ->orderBy(Trooper::NAME)
            ->get();
    }
}
```

### 9.4. Dispatching via MagicBus

Use the `MagicBus` facade or inject `MagicBus` into your controllers:

**From Controller:**
```php
use App\Bus\MagicBus;
use App\Features\Events\Commands\CreateEventCommand;

class CreateEventController extends Controller
{
    public function __invoke(Request $request, MagicBus $bus)
    {
        $event = $bus->send(new CreateEventCommand(
            organization: $request->user()->organization,
            data: $request->validated()
        ));
        
        return redirect()->route('events.show', $event);
    }
}
```

**From Job:**
```php
use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventNotificationCommand;

class SendEventNotificationsJob implements ShouldQueue
{
    public function __construct(private Event $event) {}
    
    public function handle(MagicBus $bus): void
    {
        $bus->send(new SendEventNotificationCommand($this->event));
    }
}
```

### 9.5. MagicBusController

For simple CRUD operations, use `MagicBusController` to avoid creating dedicated controller classes:

**Route:**
```php
Route::post('/admin/events/{event}/update', MagicBusController::class)
    ->defaults('command', UpdateEventCommand::class);
```

The `MagicBusController` automatically:
1. Instantiates the command from route parameters and request data
2. Dispatches it through MagicBus
3. Returns the result

### 9.6. Handler Modifiers

Handlers can use traits to modify their execution behavior:

**Transactional Execution:**
```php
use App\Bus\Concerns\ShouldBeTransactional;

readonly class CreateEventCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;  // Wraps execution in DB transaction
    
    public function __invoke(CreateEventCommand $command): Event
    {
        // All database operations here run in a transaction
    }
}
```

**Deferred Execution:**
```php
use App\Bus\Concerns\ShouldRunAfterResponse;

readonly class SendNotificationCommandHandler implements CommandHandlerInterface
{
    use ShouldRunAfterResponse;  // Runs after HTTP response is sent
    
    public function __invoke(SendNotificationCommand $command): void
    {
        // Heavy operations that don't need to block the response
    }
}
```

### 9.7. Testing Commands and Queries

**Unit Test a Handler:**
```php
public function test_invoke_creates_event(): void
{
    $organization = Organization::factory()->create();
    $data = ['name' => 'Test Event', 'event_date' => '2026-02-15'];
    
    $subject = new CreateEventCommandHandler();
    $command = new CreateEventCommand($organization, $data);
    
    $result = $subject($command);
    
    $this->assertInstanceOf(Event::class, $result);
    $this->assertEquals('Test Event', $result->name);
    $this->assertDatabaseHas('tt_events', ['name' => 'Test Event']);
}
```

**Feature Test Through MagicBus:**
```php
public function test_create_event_endpoint(): void
{
    $trooper = Trooper::factory()->asAdministrator()->create();
    
    $response = $this->actingAs($trooper)->post('/admin/events', [
        'name' => 'New Event',
        'event_date' => '2026-02-15',
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('tt_events', ['name' => 'New Event']);
}
```

### 9.8. Best Practices

**DO:**
- ✅ Make Command/Query classes readonly with public properties
- ✅ Use descriptive, action-oriented names (`CreateEvent`, not `EventCreator`)
- ✅ Keep handlers focused on a single responsibility
- ✅ Return typed results from handlers
- ✅ Use dependency injection in handler constructors
- ✅ Validate data before creating commands (in controllers/requests)

**DON'T:**
- ❌ Put business logic in the Command/Query classes (they're just DTOs)
- ❌ Access HTTP context (Request, Session) in handlers
- ❌ Create Commands for simple reads (use Queries)
- ❌ Return void from Queries (they should return data)
- ❌ Mix Commands and Queries in the same handler

### 9.9. Integration with ADR

The MagicBus pattern complements the Action-Domain-Responder architecture:

- **Action (Controller):** Validates input, creates Command/Query, dispatches via MagicBus
- **Domain (Handler):** Executes business logic, returns result
- **Responder:** Controller formats handler result into HTTP response

This separation ensures business logic is reusable and testable independent of HTTP concerns.

## 10. Testing (PHPUnit)

A robust test suite is essential for our refactoring efforts. All new features and refactored code must be accompanied by tests.

### 10.1. Test Strategy

The appropriate test type depends on the component being tested:

-   **Controllers:** Must be covered by **feature tests**. Feature tests exercise the full HTTP request/response cycle, ensuring that routing, middleware, request validation, and response rendering work correctly together.
-   **Jobs:** Must be covered by **feature tests**. Feature tests verify that jobs correctly orchestrate Service class calls and handle queue-specific concerns like failures and retries.
-   **Console Commands:** Must be covered by **feature tests**. Feature tests confirm that commands parse arguments correctly, orchestrate Service calls, and produce the expected console output.
-   **Services:** Should be covered by **unit tests**. Since Services contain the core business logic and are decoupled from framework infrastructure, they are ideal candidates for fast, focused unit tests.

This strategy ensures that our orchestration layers (Controllers, Jobs, Commands) are tested in realistic scenarios, while our business logic (Services) receives fast, isolated unit test coverage.

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
    $subject = new GetTroopersByRoleQueryHandler();
    $result = $subject(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));
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
  
- **FlashMessageService:** Manages flash messages with Bootstrap styling
  - `success(string $message): void`
  - `error(string $message): void`
  - `warning(string $message): void`
  - `info(string $message): void`
  
- **GeocodingService:** Geocodes addresses to lat/long coordinates
  - `geocode(string $address): ?array`
  
- **GoogleService:** Integrates with Google APIs
  - OAuth authentication
  - API access management

**Usage:**
```php
public function __construct(
    private readonly FlashMessageService $flash
) {}

public function store()
{
    // ...
    $this->flash->success('Event created successfully!');
}
```

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
- **EventType:** `regular`, `special`, `fundraiser`
- **MembershipRole:** `member`, `moderator`, `administrator`
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