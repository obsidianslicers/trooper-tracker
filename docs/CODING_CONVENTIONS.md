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

For new and refactored features, we will adopt the **Action-Domain-Responder (ADR)** pattern. This pattern helps to separate concerns and organize application logic cleanly.

This pattern is a practical application of several SOLID principles:

-   **Single Responsibility Principle (S):** Each component has one job.
    -   The **Action**'s responsibility is to interpret the HTTP request and orchestrate the call to the Domain. In Laravel, this is our invokable Controller.
    -   The **Domain**'s responsibility is to execute the core business logic. In our application, this layer consists of **Eloquent Models** and dedicated **Service classes** that are completely unaware of the web context.
    -   The **Responder**'s responsibility is to build the HTTP response from the data the Domain returns. This will typically be a **Blade view**, a **JSON response**, or a redirect.

-   **Dependency Inversion Principle (D):** It inverts the traditional flow of control.
    -   High-level components (Actions) depend on abstractions, not on low-level components (Domain). The Domain logic doesn't know or care that it was called by a web controller; it could just as easily be called from an Artisan command or a queue job. This decoupling makes our business logic (the most valuable part of our code) more reusable and easier to test in isolation.

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

Jobs and Console Commands should follow the **Orchestration Pattern**, acting as thin orchestrators that coordinate calls to Service classes. This keeps the business logic in the Domain layer (Service classes) where it can be reused and tested in isolation.

**Key Principles:**

-   **Jobs and Commands are Orchestrators:** They should coordinate workflow, not implement business logic.
-   **Business Logic Lives in Services:** Extract all domain logic into dedicated Service classes.
-   **Reusability:** Service classes can be called from Controllers, Jobs, Commands, or other Services.
-   **Testability:** Service classes are easier to unit test because they have no dependencies on Laravel's job/command infrastructure.

**Example:**

```php
// app/Jobs/SendEventNotificationsJob.php
class SendEventNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Event $event
    ) {
    }

    /**
     * Execute the job by orchestrating the service call.
     */
    public function handle(EventNotificationService $service): void
    {
        // Orchestrate - delegate to service
        $service->sendNotificationsForEvent($this->event);
    }
}

// app/Console/Commands/SendDailyEventDigestCommand.php
class SendDailyEventDigestCommand extends Command
{
    protected $signature = 'events:send-daily-digest';
    protected $description = 'Send daily event digest to troopers';

    /**
     * Execute the console command by orchestrating the service call.
     */
    public function handle(EventNotificationService $service): int
    {
        // Orchestrate - delegate to service
        $sent_count = $service->sendDailyDigests();
        
        $this->info("Sent {$sent_count} daily digests.");
        
        return Command::SUCCESS;
    }
}

// app/Services/EventNotificationService.php
class EventNotificationService
{
    /**
     * Business logic: Send notifications for an event.
     */
    public function sendNotificationsForEvent(Event $event): void
    {
        // Business logic here
        $troopers = $this->getEligibleTroopers($event);
        
        foreach ($troopers as $trooper) {
            $this->createNotification($event, $trooper);
        }
    }

    /**
     * Business logic: Send daily digest emails.
     */
    public function sendDailyDigests(): int
    {
        // Business logic here
        $notifications = $this->getPendingDailyNotifications();
        
        // Send and track
        $sent_count = 0;
        foreach ($notifications as $notification) {
            Mail::to($notification->trooper)->queue(
                new DailyEventNotification($notification)
            );
            $sent_count++;
        }
        
        return $sent_count;
    }
    
    // Additional private helper methods...
}
```

**Benefits:**

-   Jobs and Commands remain simple, focused, and easy to understand
-   Business logic is centralized in Services for consistency
-   Services can be reused across different entry points (web, CLI, queue)
-   Unit testing is simplified by testing Services independently
-   Aligns with the Single Responsibility Principle

## 9. Testing (PHPUnit)

A robust test suite is essential for our refactoring efforts. All new features and refactored code must be accompanied by tests.

### 9.1. Test Strategy

The appropriate test type depends on the component being tested:

-   **Controllers:** Must be covered by **feature tests**. Feature tests exercise the full HTTP request/response cycle, ensuring that routing, middleware, request validation, and response rendering work correctly together.
-   **Jobs:** Must be covered by **feature tests**. Feature tests verify that jobs correctly orchestrate Service class calls and handle queue-specific concerns like failures and retries.
-   **Console Commands:** Must be covered by **feature tests**. Feature tests confirm that commands parse arguments correctly, orchestrate Service calls, and produce the expected console output.
-   **Services:** Should be covered by **unit tests**. Since Services contain the core business logic and are decoupled from framework infrastructure, they are ideal candidates for fast, focused unit tests.

This strategy ensures that our orchestration layers (Controllers, Jobs, Commands) are tested in realistic scenarios, while our business logic (Services) receives fast, isolated unit test coverage.

### 9.2. Test Method Naming

All test method names must be `snake_cased` and begin with the `test_` prefix. The name should clearly describe what the test is asserting.

```php
public function test_invoke_handles_unapproved_user(): void
{
    // ...
}
```

### 9.3. Subject Under Test

When instantiating the class being tested, the variable name **must** be `$subject`.

```php
public function test_something(): void
{
    $subject = new MyAwesomeService();
    $result = $subject->doSomething();
    $this->assertTrue($result);
}
```

### 9.4. Mocking

When creating mocks, use **Mockery** with the `shouldReceive()` chain for setting up expectations. This provides a clear, readable format.

```php
// Example for a mock of Illuminate\Session\Store
$session_mock = Mockery::mock(Store::class);

$session_mock->shouldReceive('get')
    ->once()
    ->with('flash_messages', [])
    ->andReturn([]);
```