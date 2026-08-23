# TroopTracker — Claude Code Reference

TroopTracker is a Laravel application for managing costuming club activities — events, trooper profiles, memberships, achievements, notifications, and organization hierarchies. It supports multiple organizations with role-based access control.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2+, Laravel |
| Database | MySQL 8.0+ (SQLite in-memory for tests) |
| Frontend | Blade templates, Alpine 3.x, HTMX 2.x, Bootstrap 5.2x |
| Build | Vite, npm |
| Testing | PHPUnit |
| Code quality | Pint (formatting), PHPStan (static analysis) |

The Laravel app root is `tracker-app/`. All commands below assume you're running from there.

---

## Architecture

### Action-Domain-Responder (ADR)

- **Action**: Thin invokable controllers — validate input, dispatch via MagicBus, return response
- **Domain**: All business logic lives in `app/Features/` handlers
- **Responder**: Controllers format handler results into Blade views, redirects, or JSON

Controllers contain no business logic. They orchestrate.

### MagicBus — Command/Query Separation

Convention-based dispatcher. Handlers are auto-resolved: `CreateEventCommand` → `CreateEventCommandHandler`.

**Commands** — write operations (state change):
```php
// app/Features/Events/Commands/CreateEventCommand.php
readonly class CreateEventCommand
{
    public function __construct(
        public Organization $organization,
        public array $data
    ) {}
}

// app/Features/Events/Commands/CreateEventCommandHandler.php
readonly class CreateEventCommandHandler implements CommandHandlerInterface
{
    public function __invoke(CreateEventCommand $command): Event
    {
        return Event::create([...]);
    }
}
```

**Queries** — read operations (no side effects):
```php
// app/Features/Troopers/Queries/GetTroopersByRoleQuery.php
readonly class GetTroopersByRoleQuery
{
    public function __construct(public MembershipRole $role) {}
}

// app/Features/Troopers/Queries/GetTroopersByRoleQueryHandler.php
readonly class GetTroopersByRoleQueryHandler implements QueryHandlerInterface
{
    public function __invoke(GetTroopersByRoleQuery $query): Collection
    {
        return Trooper::where(Trooper::MEMBERSHIP_ROLE, $query->role->value)->get();
    }
}
```

**Dispatching from a controller:**
```php
use App\Bus\MagicBus;

class CreateEventController extends Controller
{
    public function __invoke(CreateEventRequest $request, MagicBus $bus)
    {
        $event = $bus->send(new CreateEventCommand(
            organization: $request->user()->organization,
            data: $request->validated()
        ));
        return redirect()->route('events.show', $event);
    }
}
```

**Handler modifier traits:**
- `ShouldBeTransactional` — wraps handler in a DB transaction
- `ShouldRunAfterResponse` — defers execution until after HTTP response is sent

### Jobs & Console Commands (Orchestration Pattern)

Jobs and Artisan commands are thin orchestrators — they dispatch Commands/Queries and contain no business logic:
```php
public function handle(MagicBus $bus): void
{
    $bus->send(new SendEventCreatedNotificationCommand($this->event));
}
```

### Feature Naming (Verb + Object)

A feature's name is `[Verb] [Object] [optional details]` (e.g. `RequestDeletion`, `UpdateProfile`, `CancelDeletion`). Two rules:

1. **Keep the same name top to bottom, where possible** — the ViewModel, controller, form request, and top-level action should share one name:
   `RequestDeletionViewModel` (svelte) → `RequestDeletionController` → `RequestDeletionRequest` → `RequestTrooperDeletion` (message).
   The message layer may swap in a more specific object noun (e.g. `Trooper` instead of the `Account` namespace it's called from) when that object is shared across multiple entry points — e.g. `RequestTrooperDeletion` lives outside the `Account` namespace because both `Account` and `Admin` can trigger it. That's the one deliberate exception to rule 1; don't let unrelated layers drift in name otherwise.
2. **Higher-intent (UI/HTTP) layers can be terser than lower-intent (message/domain) layers**, as long as the verb+object pair still matches: UI/HTTP say `UpdateProfile` / `RequestDeletion`; the message layer says `UpdateTrooperProfile` / `RequestTrooperDeletion`. The extra specificity is added, not changed.

When adding a sibling action to an existing feature (e.g. a "cancel" counterpart to "request"), name it the same way — verb first, object second, matching object noun — rather than reusing an unrelated noun or reversing the order (avoid e.g. `DeletionCancelController`; prefer `CancelDeletionController`).

---

## Svelte & Frontend Architecture

Core principle: **decomposition into small, single units of work** — reduces cognitive load, isolates bugs, and keeps testing/support fast.

### When to use Svelte vs Blade

- **Blade**: static, low-interaction, or server-rendered content (legal terms, email-confirmation landers, simple static messages).
- **Svelte**: views that genuinely benefit from client-side reactivity, complex state, or rich UI interaction.
- Do not migrate views to Svelte for stack purity. Replacing a 10-line static Blade template with a Svelte page + API route + TypeScript VM layer introduces unnecessary architectural overhead.
- Start new/refactored features with a small, simple page rather than a large one.

### Message decomposition (PageData)

In addition to Commands and Queries (see MagicBus above), page-facing endpoints get a **PageData** message:

- **Commands & Queries** stay strictly separate — neither should be overloaded to also supply multi-query page view data.
- **PageData**: aggregates multiple queries into a single response payload tailored to a specific page. Example: `GetAllOrganizationsQuery` returns full records/columns; `RegistrationPageData` pulls from that query and trims the payload to only the JSON fields the registration UI needs.
- Location: `app/Messages/[Feature]/PageData/[Name]PageData.php` (see `app/Messages/Auth/PageData/LoginPageData.php` for reference).

**Calling conventions:**
- Page requests: `PageData::call($request)` — automatically hydrates constructor parameters from request inputs, query strings, route parameters, and route model bindings.
- Everywhere else: explicit named arguments (e.g. `Query::call(organization_id: 123)`) to keep dependencies clear and refactoring safe.
- Maintain PHPDoc annotations on static `::call()` methods to preserve constructor autocomplete/Intellisense.
- For messages needing the authenticated user, inject `Actor $actor` in the constructor — this explicitly distinguishes the active session user from target user records fetched via lookup.

### Svelte directory structure

```
resources/svelte/
├── lib/domains/[feature]/
│   └── vms/
│       └── [PageViewModel].svelte.ts   # One file per VM; page logic, state, & types
└── pages/[feature]/
    ├── components/                     # Single-responsibility, reusable UI components
    │   └── ComponentName.svelte
    └── PageName.svelte
```

- `lib/domains/[feature]/vms/`: each ViewModel handles a single unit of work supporting one specific page. Keep helper functions, types, and logic contained within its VM file.
- `pages/[feature]/`: pure presentation layer. Components and pages delegate business/state logic to their VM. Simple pages may not need a VM — use judgment.
- Reference implementation: the auth/Login module (`resources/svelte/lib/domains/auth/vms/LoginViewModel.svelte.ts`).

---

## Key Directory Map

```
tracker-app/
├── app/
│   ├── Features/              # Domain logic, organized by feature
│   │   ├── Events/
│   │   │   ├── Commands/      # e.g. CreateEventCommand + CreateEventCommandHandler
│   │   │   └── Queries/       # e.g. GetEventsByOrganizationQuery + Handler
│   │   ├── Troopers/
│   │   ├── Organizations/
│   │   ├── Reports/           # Queries only
│   │   ├── Notices/
│   │   └── Changes/           # Queries only (audit trail)
│   ├── Http/Controllers/      # Invokable single-action controllers
│   ├── Models/                # Extended Eloquent models — add custom logic here
│   ├── Models/Base/           # Auto-generated from schema — NEVER EDIT
│   ├── Bus/                   # MagicBus dispatcher + interfaces
│   ├── Jobs/                  # Queueable jobs (orchestration only)
│   ├── Console/Commands/      # Artisan commands (orchestration only)
│   ├── Policies/              # Authorization (HasTrooperPermissionsTrait)
│   ├── Rules/                 # Custom validation rules, organized by feature
│   └── Services/              # Standalone services (Flash, BreadCrumb, Geocoding, Google)
├── tests/
│   ├── Feature/               # HTTP, Job, Console Command tests
│   └── Unit/                  # Handler, Policy, Rule, Service tests
└── docs/                      # Full documentation (see links at bottom)
```

---

## Coding Conventions

### Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `CreateEventCommandHandler`, `LoginSubmitController` |
| Methods / Functions | camelCase | `handleRequest()`, `findUserById()` |
| Parameters & local variables | snake_case | `$user_name`, `$local_variable` |
| Class properties | snake_case | `private string $class_property` |
| Test methods | `test_` + snake_case | `test_invoke_creates_event()` |

### Style

- Explicit scalar type hints on all parameters and return types — always
- Lines ≤ 100 characters
- Methods ≤ 30 lines; extract private helpers if needed
- No comments unless the WHY is non-obvious
- `declare(strict_types=1);` at the top of every PHP file

### Controllers

Always invokable (single-action). One controller = one action.

```php
// app/Http/Controllers/Events/CreateEventController.php
class CreateEventController extends Controller
{
    public function __invoke(CreateEventRequest $request, MagicBus $bus) { ... }
}

// routes/web.php
Route::post('/events', CreateEventController::class);
```

For simple CRUD where no dedicated controller class is needed, use `MagicBusController`:
```php
Route::post('/admin/events/{event}/update', MagicBusController::class)
    ->defaults('command', UpdateEventCommand::class);
```

---

## Database Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Table names | Plural `snake_case` | `troopers`, `event_troopers` |
| Column names | `snake_case` | `first_name`, `event_date` |
| Boolean columns | `is_`, `can_`, `has_`, `should_` prefix | `is_verified`, `has_limits` |
| Primary key | `id` | Auto-incrementing integer |
| Foreign keys | `singular_table_id` | `trooper_id`, `event_id` |
| Pivot tables | Alphabetized singular names | `event_trooper` |
| Timestamps | `created_at`, `updated_at` | Standard Eloquent |
| Soft deletes | `deleted_at` | SoftDeletes trait |

After any schema change, regenerate base models:
```bash
php artisan code:models
php artisan fabricator:generate-factories
```

Do not edit anything in `app/Models/Base/` — it is overwritten on regeneration.

---

## Testing

### Test type by component

| Component | Test type | Reason |
|-----------|----------|--------|
| Controllers | Feature | Full HTTP request/response cycle |
| Jobs | Feature | Queue concerns + orchestration |
| Console Commands | Feature | Argument parsing + console output |
| Handlers (Commands/Queries) | Unit | Isolated business logic |
| Policies | Unit | Authorization logic in isolation |
| Rules | Unit | Validation logic in isolation |
| Services | Unit | Isolated, no framework infrastructure |

### Conventions

- Subject under test is always `$subject`
- Mocking: Mockery with `shouldReceive()` chain
- Factory states for setup: `asAdministrator()`, `asActive()`, etc.
- Test DB: SQLite in-memory (configured in `phpunit.xml`)

```php
public function test_invoke_creates_event(): void
{
    $organization = Organization::factory()->create();

    $subject = new CreateEventCommandHandler();
    $result = $subject(new CreateEventCommand($organization, ['name' => 'Test Event']));

    $this->assertInstanceOf(Event::class, $result);
    $this->assertDatabaseHas('tt_events', ['name' => 'Test Event']);
}
```

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

```bash
# Run tests
php artisan test
php artisan test --filter=TestClassName

# Code quality
./vendor/bin/pint                         # Format code
./vendor/bin/phpstan                      # Static analysis

# Schema changes (after running migrations)
php artisan code:models                   # Regenerate base models
php artisan fabricator:generate-factories    # Regenerate factories

# Make scaffolding
php artisan make:controller NameController --invokable
php artisan make:policy NamePolicy
php artisan make:rule NameRule

# Cache (development)
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Assets
npm run dev      # Dev server with HMR
npm run build    # Production build
```

---

## Enums

All enums are backed strings using `HasEnumHelpers`:
- `AchievementType`, `AwardFrequency`, `EventStatus`, `EventTrooperStatus`, `EventType`
- `MembershipRole` (`member`, `moderator`, `administrator`)
- `MembershipStatus` (`pending`, `active`, `retired`)
- `NoticeType`, `NotificationFrequency`, `OrganizationType`, `TrooperTheme`

```php
MembershipRole::toArray();     // ['member' => 'Member', ...]
EventStatus::toValidator();    // 'draft,open,closed,cancelled'
```

---

## Authorization

Policies live in `app/Policies/` and use `HasTrooperPermissionsTrait`:
```php
$this->authorize('update', $event);  // Calls EventPolicy::update()
```

Available policies: `AwardPolicy`, `EventPolicy`, `NoticePolicy`, `OrganizationPolicy`, `TrooperPolicy`.

---

## Full Documentation

| Topic | File |
|-------|------|
| Architecture deep-dive | `docs/ARCHITECTURE.md` |
| Coding conventions | `docs/CODING_CONVENTIONS.md` |
| Project directory structure | `docs/PROJECT_STRUCTURE.md` |
| Database schema & ERD | `docs/DATABASE.md` |
| Environment variables | `docs/ENVIRONMENT_VARIABLES.md` |
| Authentication flow | `docs/AUTHENTICATION.md` |
| Console commands reference | `docs/COMMANDS.md` |
| Deployment | `docs/DEPLOY.md` |
