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

- **Action**: Thin invokable controllers — validate input, call a message, return response
- **Domain**: All business logic lives in Hyperdrive messages in `app/Messages/`
- **Responder**: Controllers format message results into Blade views, redirects, or JSON

Controllers contain no business logic. They orchestrate.

### Hyperdrive Messages — Command/Query Separation

Each operation is one class in `app/Messages/[Feature]/{Commands,Queries,PageData}/` that extends
`Hyperdrive\Message`. The constructor is the input; `handle()` does the work. There are no handler
classes and no `Command`/`Query` suffixes. Shared traits live in `app/Messages/Concerns/`.

**Commands** — write operations (state change):
```php
// app/Messages/Troopers/Commands/VoidTrooper.php
/**
 * @method static void call(Trooper $trooper)
 */
final class VoidTrooper extends Message
{
    use ShouldBeTransactional;

    public function __construct(private readonly Trooper $trooper) {}

    public function handle(): void
    {
        // ...
    }
}
```

**Queries** — read operations (no side effects):
```php
// app/Messages/Troopers/Queries/GetTroopersByRole.php
/**
 * @method static Collection<int, Trooper> call(MembershipRole $membership_role)
 */
final class GetTroopersByRole extends Message
{
    public function __construct(private readonly MembershipRole $membership_role) {}

    public function handle(): Collection
    {
        return Trooper::active()
            ->where(Trooper::MEMBERSHIP_ROLE, $this->membership_role)
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();
    }
}
```

**Calling a message from a controller:**
```php
// app/Http/Controllers/Admin/Troopers/Commands/VoidTrooperController.php
class VoidTrooperController extends Controller
{
    public function __invoke(Request $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('void', $trooper);

        VoidTrooper::call(trooper: $trooper);

        return redirect()->route('admin.troopers.show', compact('trooper'));
    }
}
```

- Inject services into `handle()` (e.g. `handle(XenforoService $xenforo)`), never the constructor.
- `Hyperdrive\Concerns\ShouldBeTransactional` wraps `handle()` in a DB transaction.
- Work that shouldn't block the response goes in a queued Job that calls the message.

### Jobs & Console Commands (Orchestration Pattern)

Jobs and Artisan commands are thin orchestrators — they call messages and contain no business logic:
```php
public function handle(): void
{
    SendEventCreatedNotification::call(event: $this->event, trooper: $trooper);
}
```

### Feature Naming (Verb + Object)

A feature's name is `[Verb] [Object] [optional details]` (e.g. `RequestDeletion`, `UpdateProfile`, `CancelDeletion`). Two rules:

1. **Keep the same name top to bottom, where possible** — the ViewModel, controller, form request, and top-level action should share one name:
   `RequestDeletionViewModel` (svelte) → `RequestDeletionController` → `RequestDeletionRequest` → `RequestTrooperDeletion` (message).
   The message layer may swap in a more specific object noun (e.g. `Trooper` instead of the `Account` namespace it's called from) when that object is shared across multiple entry points — e.g. `RequestTrooperDeletion` lives outside the `Account` namespace because both `Account` and `Admin` can trigger it. That's the one deliberate exception to rule 1; don't let unrelated layers drift in name otherwise.
2. **Higher-intent (UI/HTTP) layers can be terser than lower-intent (message/domain) layers**, as long as the verb+object pair still matches: UI/HTTP say `UpdateProfile` / `RequestDeletion`; the message layer says `UpdateTrooperProfile` / `RequestTrooperDeletion`. The extra specificity is added, not changed.

When adding a sibling action to an existing feature (e.g. a "cancel" counterpart to "request"), name it the same way — verb first, object second, matching object noun — rather than reusing an unrelated noun or reversing the order (avoid e.g. `DeletionCancelController`; prefer `CancelDeletionController`).

**Every Command message follows `[Verb][Object][Optional Detail]`.** The name starts with a single
verb, then the object it acts on, then any detail: `ExecuteTrooperDeletion`, `AddTrooperDirectly`,
`MarkNoticeAsRead`, `UnrevokeTrooperCostume`. Avoid:

- a missing object (`Login` → `LoginTrooper`)
- a modifier or a second verb before the object (`DirectAddTrooper`, `UndoRevokeTrooperCostume`)
- an object noun that differs from its siblings (`ExecuteAccountDeletion` next to
  `RequestTrooperDeletion`; `AddCostumeToTrooper` next to `ApproveTrooperCostume`)
- a noun used as the verb (`SetupTrooper` → `SetUpTrooper`)

Traits shared by messages go in `app/Messages/Concerns/`, not in a `Commands` folder.

---

## Svelte & Frontend Architecture

Core principle: **decomposition into small, single units of work** — reduces cognitive load, isolates bugs, and keeps testing/support fast.

### When to use Svelte vs Blade

- **Blade**: static, low-interaction, or server-rendered content (legal terms, email-confirmation landers, simple static messages).
- **Svelte**: views that genuinely benefit from client-side reactivity, complex state, or rich UI interaction.
- Do not migrate views to Svelte for stack purity. Replacing a 10-line static Blade template with a Svelte page + API route + TypeScript VM layer introduces unnecessary architectural overhead.
- Start new/refactored features with a small, simple page rather than a large one.

### Message decomposition (PageData)

In addition to Commands and Queries (see Hyperdrive Messages above), page-facing endpoints get a **PageData** message:

- **Commands & Queries** stay strictly separate — neither should be overloaded to also supply multi-query page view data.
- **PageData**: aggregates multiple queries into a single response payload tailored to a specific page. Example: `GetAllOrganizationsQuery` returns full records/columns; `RegistrationPageData` pulls from that query and trims the payload to only the JSON fields the registration UI needs.
- Location: `app/Messages/[Feature]/PageData/[Name]PageData.php` (see `app/Messages/Auth/PageData/LoginPageData.php` for reference).

**Calling conventions:**
- Page requests: `PageData::call($request)` — automatically hydrates constructor parameters from request inputs, query strings, route parameters, and route model bindings.
- Everywhere else: explicit named arguments (e.g. `Query::call(organization_id: 123)`) to keep dependencies clear and refactoring safe.
- Maintain PHPDoc annotations on static `::call()` methods to preserve constructor autocomplete/Intellisense.
- For messages needing the authenticated user, inject `Actor $actor` in the constructor — this explicitly distinguishes the active session user from target user records fetched via lookup.

### Styling

Style with CSS classes first: Bootstrap's own, then the shared ones in `resources/css/app.scss`
(e.g. `btn-secondary-action`). Only when no class fits, customise in the Svelte component's own
`<style>` block (or in `app.scss` for Blade). No inline `style=`, and no utility classes used to
undo what a class already sets.

Buttons look the same everywhere, on Blade and Svelte pages:

- Every `<button>` has a class. A button is `.btn` plus a colour class (`btn-primary`,
  `btn-outline-danger`, `btn-secondary-action`, ...).
- Size comes only from Bootstrap's `btn-sm` / `btn-lg`. Don't change a button's padding, height,
  font size, font, weight or case: the theme makes every button uppercase in its own font.
- A link-style action (Never mind, Download CSV) is `btn-link` and stays a normal-case text link.

Links take you somewhere; buttons do something. Prefer an `<a>` that looks like a link for going
to another page, and a `<button>` that looks like a button for an action (save, sign up, cancel,
open a form). This is a strong default, not a hard rule: a page's main call to action may be a link
styled as a button (Login on the home page), and a quiet, low-stakes action may be a text-style
`btn-link` (Never mind).

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
│   ├── Messages/              # Domain logic (Hyperdrive messages), organized by feature
│   │   ├── Concerns/          # Traits shared by messages
│   │   ├── Events/
│   │   │   ├── Commands/      # e.g. UpdateEvent
│   │   │   └── Queries/       # e.g. GetEventsForDisplay
│   │   ├── Troopers/          # Commands, Queries, PageData
│   │   ├── Organizations/
│   │   ├── Reports/           # Queries only
│   │   ├── Notices/
│   │   └── Changes/           # Queries only (audit trail)
│   ├── Http/Controllers/      # Invokable single-action controllers
│   ├── Models/                # Extended Eloquent models — add custom logic here
│   ├── Models/Base/           # Auto-generated from schema — NEVER EDIT
│   ├── Jobs/                  # Queueable jobs (orchestration only)
│   ├── Console/Commands/      # Artisan commands (orchestration only)
│   ├── Plugins/               # Plugin system: manager, hook base provider, registries
│   ├── Policies/              # Authorization (HasTrooperPermissionsTrait)
│   ├── Rules/                 # Custom validation rules, organized by feature
│   └── Services/              # Standalone services (Flash, BreadCrumb, Geocoding, Google)
├── plugins/<slug>/            # Local plugins (discord, google-sheets, holonet, sentry, xenforo); see docs/PLUGINS.md
│   ├── src/                   # Plugin code, namespace from plugin.json
│   └── tests/                 # Plugin tests (`<namespace>\Tests`, Plugins suite)
├── tests/
│   ├── Feature/               # HTTP, Job, Console Command tests
│   └── Unit/                  # Message, Policy, Rule, Service tests
└── docs/                      # Full documentation (see links at bottom)
```

---

## Coding Conventions

### Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `GetTroopersByRole`, `LoginTrooperController` |
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

Each feature folder splits its controllers in two:

- `Commands/`: the request changes something: the database, or who is logged in. This goes by
  what the controller does, not the HTTP verb: logout, email verification and the OAuth callback
  are GETs that live in `Commands/`.
- `Pages/`: the request changes nothing. Pages, plus downloads (ICS, CSV), JSON lookups (pickers,
  search) and HTMX fragments.

Controller names follow the same `[Verb][Object][Optional Detail]` rule as Command messages, and
share the message's name where there is one (`LoginTrooperController` → `LoginTrooper`). Pages
start with `Show` (one page or thing), `List` (a list), `Download`, or `Search`; a page that is
the form for a command is `Show[Thing]FormController` (`ShowLoginFormController`,
`ShowSetupFormController`). Drop `Submit`: the form's POST is named after what it does.

A controller's form request mirrors its path and name: `Controllers/Events/Commands/UpdateTrooperSignUpController`
uses `Requests/Events/Commands/UpdateTrooperSignUpRequest`, and its test is
`tests/Feature/Http/Requests/Events/Commands/UpdateTrooperSignUpRequestTest`. Rules shared by
several requests stay as a trait in the feature folder (`Requests/Admin/Events/CommonRules`).

```
app/Http/Controllers/Events/
├── Commands/SignUpTrooperController.php      # POST: signs a trooper up for a shift
├── Commands/CompleteShiftController.php      # GET from an email link: records attendance
├── Pages/ListEventsController.php
├── Pages/ShowEventController.php
└── Pages/DownloadEventIcsController.php
```

**Admin (Command Staff) is stricter: it follows Laravel's resource conventions.** For plain CRUD,
the controller verb, route name and URL come from Laravel's resource actions. Every write is a
POST (no PUT/PATCH/DELETE), so forms and Svelte send with `post`:

| Action | Controller | Method + URL | Route name |
|---|---|---|---|
| index | `Pages/IndexAwardsController` | `GET /admin/awards` | `admin.awards.index` |
| create | `Pages/CreateAwardController` | `GET /admin/awards/create` | `admin.awards.create` |
| store | `Commands/StoreAwardController` | `POST /admin/awards` | `admin.awards.store` |
| show | `Pages/ShowTrooperController` | `GET /admin/troopers/{trooper}` | `admin.troopers.show` |
| edit | `Pages/EditAwardController` | `GET /admin/awards/{award}/edit` | `admin.awards.edit` |
| update | `Commands/UpdateAwardController` | `POST /admin/awards/{award}` | `admin.awards.update` |
| destroy | `Commands/DestroyCostumeController` | `POST /admin/costumes/{costume}/delete` | `admin.costumes.destroy` |

- The controller name is the resource verb plus the object, even when the message says something
  else (`UpdateAwardController` calls `RenameAward`).
- A GET page and its POST never share a route name: the form page is `create`/`edit`, and the form
  posts to `store`/`update`.
- Nested resources nest the name: `admin.awards.troopers.create` / `.store` / `.destroy`
  (`CreateAwardTrooperController`). So do parts of one resource that are saved on their own:
  `admin.troopers.guardian.update`, `admin.events.shifts.edit` / `.update`.
- Actions that aren't CRUD keep their own verb, named like the message: `VoidTrooperController`
  (`admin.troopers.void`), `ApproveCostumeController`, `ReorderFaqItemsController`. If such an
  action has a form page, the page is `Show[Thing]FormController` on `[action].create` and the
  POST is `[action].store`, on the same URL (`/admin/troopers/merge`:
  `ShowMergeTroopersFormController`, `MergeTroopersController`).

```php
// routes/web/admin-awards.php
Route::get('/', IndexAwardsController::class)->name('index');
Route::get('/create', CreateAwardController::class)->name('create');
Route::post('/', StoreAwardController::class)->name('store');
Route::get('/{award}/edit', EditAwardController::class)->name('edit');
Route::post('/{award}', UpdateAwardController::class)->name('update');
```

Front-end URLs and route names stay as they are, because people bookmark them. Admin routes may
change, but redirect any old URL that went out in an email or notification, with a dated comment
saying when to remove it (see `routes/web/admin-troopers.php`).

Tests mirror the controller: `tests/Feature/Http/Controllers/<same path>Test.php`.

Controllers that render pages extend `PageController`, which provides the breadcrumb service
(`$this->crumbs`) and an `initialized()` hook.

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
| Plugin tables | `tt_<core entity>_<plugin>[_<things>]` (see `docs/PLUGINS.md`) | `tt_organization_google_sheets`, `tt_trooper_holonet_links` |

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
| Messages (Commands/Queries/PageData) | Unit | Isolated business logic |
| Policies | Unit | Authorization logic in isolation |
| Rules | Unit | Validation logic in isolation |
| Services | Unit | Isolated, no framework infrastructure |
| Plugin code (`plugins/<slug>/src`) | `plugins/<slug>/tests` | Runs in the `Plugins` suite; core tests must not depend on a real plugin |
| Front-end modules (`resources/js`) | Vitest | Plain JS logic such as the request parsers; `tests/js` mirrors `resources/js` |
| Pages and user flows | Playwright (`tests/e2e`) | Real browser against a seeded app; see `docs/E2E_TESTING.md` |

### Conventions

- Subject under test is always `$subject`
- Mocking: Mockery with `shouldReceive()` chain
- Stubbing messages in controller/job/console tests: `Tests\Concerns\FakesMessages`
- Factory states for setup: `asSystemOwner()`, `asActive()`, etc.
- Test DB: SQLite in-memory (configured in `phpunit.xml`)
- Front-end: `npm test` runs Vitest (`vitest.config.js`, `tests/js/**/*.test.js`)
- Before calling a change done, follow `.claude/skills/verify-changes/SKILL.md`: the tests that cover it first, then the gates CI runs

```php
public function test_handle_returns_troopers_with_role(): void
{
    $admin = Trooper::factory()->asSystemOwner()->create();

    $subject = new GetTroopersByRole(MembershipRole::SYSTEM_OWNER);
    $result = $subject->handle();

    $this->assertTrue($result->contains($admin));
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
php artisan test --parallel               # Whole suite, one process per core (~10 s)
php artisan test --filter=TestClassName
php artisan test --testsuite=Plugins      # Plugin tests only
npm test                                  # Front-end unit tests (Vitest)
npm run test:e2e                          # Browser tests (Playwright, needs npm run build)
npm run test:e2e:parallel                 # Same, split over several app servers (about half the time)

# Translations
php artisan tracker:report-translations --strict  # Missing English lines / untranslated per language

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

## Commit Messages

Format: `<type>: <short all lower case message>`. PRs are squash-merged, so the PR title becomes
the commit on `main`: give PR titles the same format.

| Type | When |
|------|------|
| `feature` | Something new people can do or see |
| `fix` | Something that behaved wrongly now behaves correctly |
| `style` | UI layout or look only, with no logic changed: CSS, or classes and markup in a view or Svelte component |
| `refactor` | Restructuring or renaming with no change in behaviour |
| `perf` | Faster or lighter, same behaviour |
| `test` | Tests only (PHPUnit, Vitest, Playwright) |
| `docs` | Documentation only (`docs/`, CLAUDE.md, READMEs) |
| `chore` | Everything else: dependencies, CI, build and tooling config, cleanup |

- One spelling per type: `feature`, not `feat`; `fix`, not `bug`; `docs`, not `doc`.
- A change that fits more than one type takes the one that matters most to users:
  `feature` > `fix` > `perf` > `style` > `refactor` > `test` / `docs` > `chore`. A layout change
  that also changes a condition, prop, PHP or TypeScript is a `fix` or `feature`, not `style`.

```
fix: station capacity is a number input
feature: create event in the events menu for everyone who can create events
style: support the garrison button is full width on phones
```

---

## Enums

All enums are backed strings using `HasEnumHelpers`:
- `AchievementType`, `AwardFrequency`, `EventStatus`, `EventTrooperStatus`, `EventType`
- `MembershipRole` (`member`, `handler`, `visitor`, `event_coordinator`, `moderator`, `staff_officer`, `system_owner`); `staff_officer` is like a System Owner without Plugins, System Check, or Merge (`is_administrator` vs `has_system_owner`)
- `MembershipStatus` (`pending`, `active`, `retired`)
- `NoticeType`, `NotificationFrequency`, `OrganizationType`, `TrooperTheme`

```php
MembershipRole::toArray();     // ['member' => 'Member', ...]
EventStatus::toValidator();    // 'draft,open,closed,cancelled'
```

---

## Translations

The app is multi-language. **Never hardcode text people read**: every new string in a view, Svelte page, flash message, validation message, notification or email subject goes through the translator, and its English line goes in `lang/en` in the same change.

Strings are keyed by the English text, in one PHP file per feature: `lang/<locale>/<feature>.php` (`events.php`, `admin.php`, ...; `common.php` is shared by every page). A locale missing a line falls back to English.

- **Blade/PHP:** `__('events.Sign up')`, `__('common.Hello, :name', ['name' => $name])`. Every key must be in `lang/en/<feature>.php`.
- **Svelte:** `import { t } from '$lib/i18n'` then `t('Sign up')`. `common` is on every page, plus the group named after the route (`events.*` routes get `events`); PageData can add groups as a `lang` prop via `TranslationService::groups()`.
- **Plain JS on Blade pages:** `import { t } from '../i18n.js'` (reads `common`).
- **Whole sentences:** never build a sentence by joining pieces (`'Hello '.$name`, `$a.' has '.$verb`). Use one line with `:placeholders`, so translators can reorder words.
- **Counts:** `trans_choice('events.:count troop|:count troops', $n)`; Svelte `t(key, replace, count)`.
- **HTML inside a line:** put the markup in a placeholder and escape the value: `{!! __('admin.Delete :name?', ['name' => '<strong>'.e($name).'</strong>']) !!}`.
- **Dates and numbers:** `$date->localFormat('M j, Y')` (patterns in `lang/<locale>/formats.php`) and `format_number()`, not `->format()` / `number_format()`, for anything people read.
- **Enums:** show `$enum->label()`, not `to_title($enum->name)`; labels live in `lang/<locale>/enums.php`.
- **Emails:** subjects use the `emails` group, in-app notification text the `notifications` group. Email bodies are whole views per language in `resources/views/emails/<locale>/`; render them with `email_view('events.trooper-signup')`, which falls back to `emails/en`. Mail and notifications render in the recipient's language.
- **Plugins:** `plugins/<slug>/lang/<locale>/<group>.php`, used as `__('<slug>::<group>.Text')`. plugin.json text (description, setting labels and help) goes in the plugin's `lang/en/plugin.php`; labels passed to `addSystemCheck`, `addNotificationCategory` or `addClubSynchronizer` are keys (`'<slug>::plugin.Forum Reply'`), translated when shown.
- **Keep in English:** log lines, exception messages, Artisan output, CSV export headers, System Check diagnostics, and stored data.
- **Checks:** `TranslationFilesTest` fails on keys missing from English. Run `php artisan tracker:report-translations --strict` before a PR: it fails on keys used in code but missing from `lang/en`. CI posts the report in each run's summary.
- **New features:** plan the text with translation in mind (whole sentences, plurals, per-locale email views).

See `docs/LOCALIZATION.md` for adding a language and the full details.

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
| Plugins (hooks, slots, settings) | `docs/PLUGINS.md` |
| Browser tests & coverage plan | `docs/E2E_TESTING.md` |
| Docker (dev stack, isolated test runs) | `docs/DOCKER.md` |
| Translations & languages (start here for any user-facing text) | `docs/LOCALIZATION.md` |
