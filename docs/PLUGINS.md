# Plugins

Plugins add features to TroopTracker without changing core code. A plugin can listen for app events, run code before or after Hyperdrive messages, add markup to the UI, and ship its own routes, views, migrations, config, settings and Artisan commands.

Plugins are full PHP code that runs with the same access as the app, so only install plugins you trust. They are installed by deploying them (git or Composer) or, when the server allows it, by uploading a zip under Command Staff > Plugins.

Every plugin is **off** until an administrator enables it. Enabling is site-wide.

---

## Managing plugins

- **Admin > Command Staff > Plugins** (administrators only) lists every plugin found, with Enable / Disable / Uninstall buttons and a Settings page for plugins that declare settings.
- From the command line:

```bash
php artisan plugins:list
php artisan plugins:enable <slug>      # also runs the plugin's migrations
php artisan plugins:disable <slug>     # keeps the plugin's data and settings
php artisan plugins:uninstall <slug>   # disables it and deletes its data (asks first)
php artisan plugins:upgrade [slug]     # runs new migrations after a deploy brought new plugin files
```

**Enable** first checks the plugin's requirements (see `requires` under [plugin.json](#pluginjson)): the PHP version, PHP extensions, Composer packages, the plugin API version, and any other plugins it needs, which must be installed at the right version and turned on first. If anything is missing nothing runs, and the Plugins page and `plugins:enable` say what is missing; the Plugins page also shows it next to the plugin and greys out Enable. A plugin another enabled plugin needs can't be disabled or uninstalled until that one is turned off. If an enabled plugin's requirements stop being met, for example after a PHP change or because a plugin it needs failed to load, it is skipped with a log warning instead of loaded, and plugins load after the plugins they need.

Then enable runs the plugin's migrations as one unit. If one fails, the migrations that already ran in that attempt are rolled back, the plugin stays disabled, and the Plugins page shows the error. Fix the plugin and enable it again. MySQL can't undo the half-run migration itself, so a plugin migration should start by dropping anything an earlier failed run of it may have left (`Schema::dropIfExists(...)` before `Schema::create(...)`).

**Upgrade** applies a newer version of a plugin's files without touching its settings or data. `tt_plugins.installed_version` is the version whose migrations last ran; when the files are newer, the Plugins page shows the update as pending. Run `php artisan plugins:upgrade` after each deploy (the deploy's `php artisan migrate` already runs enabled plugins' new migrations; `plugins:upgrade` runs any that are left and records the new version). A failed upgrade is rolled back the same way as a failed enable.

Every install, upgrade, enable, disable, uninstall and failure is recorded in `tt_plugin_changes`, with the versions and the migrations that ran.

**Disable** keeps everything, so re-enabling picks up where it left off. **Uninstall** rolls back the plugin's migrations (its `down()` methods drop its tables) and deletes its settings; this cannot be undone. Uninstall deletes the files of a plugin that was uploaded from the app. It leaves the files of a deployed plugin alone, because the next deploy would bring them back.

### Adding and removing plugin files

There are three ways to put a plugin on the server:

- **In this repo:** add the folder under `tracker-app/plugins/` (see `make:plugin`), commit, deploy.
- **As a Composer package:** `composer require vendor/plugin`, commit `composer.json`/`composer.lock`, deploy.
- **Upload a zip** under Command Staff > Plugins > Install a plugin. This is off unless the server sets `TRACKER_PLUGINS_ALLOW_UPLOADS=true` (see below).

The plugin then appears on the Plugins page as Disabled. To remove one for good, uninstall it first, then delete the folder (or `composer remove`) and deploy.

Enabling or disabling a plugin rebuilds the config and route caches if they are cached, and restarts the queue workers, so the change reaches every request and worker.

A plugin that throws while loading or booting is logged and skipped. It cannot take the site down. It shows as "Failed to load" on the Plugins page.

### Installing from the app

An uploaded plugin can run any PHP on the server once it is enabled, so this is turned off by default. Turn it on only if you trust every administrator with server access:

```dotenv
TRACKER_PLUGINS_ALLOW_UPLOADS=true
# Optional:
TRACKER_PLUGINS_UPLOADS_PATH=/path/outside/the/checkout   # default: storage/app/plugins
TRACKER_PLUGINS_MAX_UPLOAD_KB=10240
TRACKER_PLUGINS_MAX_UNZIPPED_KB=51200
TRACKER_PLUGINS_MAX_UPLOAD_FILES=2000
```

The zip holds the plugin folder: `plugin.json` either at the top of the zip or inside one top folder (`greeter/plugin.json`). A local plugin's `namespace` is required, as for any local plugin. An upload cannot install packages (see [Packages a plugin needs](#packages-a-plugin-needs)), so if the zip's `composer.json` requires anything the app doesn't already have, the upload is refused and names what is missing.

Before anything is written, the upload is refused if it:

- is not a zip, or is bigger than the size limit, before or after unzipping, or holds too many files
- has an absolute path, a `..` segment, a backslash or a symbolic link
- has no valid `plugin.json`, or has files outside the plugin folder
- uses the slug of a plugin that came with a deploy or Composer, or is not newer than the uploaded version already installed

Files are unpacked into a temporary folder and moved into place only once every file is written. The plugin is installed **disabled**, so none of its code runs until an administrator enables it. Each upload is written to the log with the administrator's trooper ID.

Uploaded plugins go to `storage/app/plugins`, outside the git checkout, so a deploy (`git reset --hard`) never removes or overwrites them. The Plugins page lists them with the source "Uploaded".

To update an uploaded plugin, upload the newer version. Its `version` must be higher than the installed one. The old files are set aside in `.previous/<slug>`, the new ones are moved in, and, if the plugin is enabled, its new migrations run. If they fail, they are rolled back and the old files are put back, so the site keeps running the old version. Settings and data are kept either way. A disabled plugin's migrations run when it is next enabled.

### Packages a plugin needs

There are three ways to install a plugin. Packages are only ever installed by Composer during a deploy, never from a web request.

- **Bundled plugins** live in `tracker-app/plugins/` and are found automatically. One with no packages of its own needs nothing else. One that needs outside PHP packages declares them in its own `composer.json` and is installed with `composer require trooptracker/<slug>` (the app's `composer.json` has a path repository for `plugins/*`), which pulls in its packages and symlinks the folder into `vendor/`. Until then, enabling it is refused and names the missing packages. Keep a `version` in the plugin's `composer.json` so the lock file does not depend on the branch name.
- A server lists the Composer plugins it uses in `TRACKER_COMPOSER_PLUGINS` in `.env`, and `deploy.sh` requires them after `composer install` (see DEPLOY.md). This works for bundled plugins with packages and for Composer-managed ones.
- **Composer-managed plugins** live in their own repository, declare their packages in their `composer.json`, and are installed with `composer require vendor/plugin`. Composer resolves them into the app's shared `vendor/` as one set, so a plugin can't pull in a version that clashes with the app's. Install and remove them with a deploy.
- **Uploaded plugins** (Plugins page) can only use what the app already has. On upload, the plugin's `composer.json` `require` list is checked against the installed packages, PHP extensions and PHP version, and the zip is refused with the list of what is missing.

The app's own `composer.json` never lists a plugin's packages. Removing a plugin and its packages leaves the app booting and its tests passing; only that plugin's features go away.

---

## Where plugins come from

1. **Local folder.** Any folder in `tracker-app/plugins/` that holds a `plugin.json`. Set `TRACKER_PLUGINS_PATH` to use another folder.
2. **Uploaded folder.** Plugins installed from the app, in `storage/app/plugins` (`TRACKER_PLUGINS_UPLOADS_PATH`).
3. **Composer packages** of type `trooptracker-plugin` with a `plugin.json` at the package root. Composer autoloads them, so they can live in their own repository and be shared with other garrisons.

If two plugins use the same slug, the Composer package wins, then the local folder, and the other is skipped with a log warning.

---

## Writing a plugin

Start from the generator:

```bash
php artisan make:plugin "Event Weather"                      # plugins/event-weather
php artisan make:plugin "Event Weather" --namespace="Acme\\Weather"
php artisan make:plugin "Event Weather" --composer           # also writes composer.json
```

It writes a working plugin with an example setting, an `EventPosted` listener, a view in the event page slot and a route, then tells you how to enable it. The templates live in `tracker-app/stubs/plugin`. The layout is:

```
plugins/hello/
├── plugin.json
├── config/config.php              # optional, merged into config('plugins.hello')
├── routes/web.php                 # optional, /plugins/hello, plugins.hello.*, signed-in troopers
├── routes/admin.php               # optional, /plugins/hello/admin, plugins.hello.admin.*, administrators
├── routes/public.php              # optional, /plugins/hello, plugins.hello.*, anyone
├── resources/views/               # optional, as the hello:: view namespace
├── database/migrations/           # optional, run on enable and by php artisan migrate
└── src/
    └── HelloServiceProvider.php
```

### plugin.json

```json
{
    "slug": "hello",
    "name": "Hello",
    "version": "1.0.0",
    "description": "Says hello.",
    "author": "Your Garrison",
    "namespace": "Acme\\Hello",
    "provider": "Acme\\Hello\\HelloServiceProvider",
    "capabilities": ["events", "forms"],
    "requires": {
        "trooptracker": "^1.0",
        "php": ">=8.2",
        "ext-intl": "*",
        "plugins": { "xenforo": "^1.0" }
    },
    "settings": [
        { "key": "greeting", "label": "Greeting", "type": "text", "default": "Hello" },
        { "key": "api_key", "label": "API key", "type": "secret", "help": "Shown here as help text." }
    ]
}
```

| Field | Required | Notes |
|-------|----------|-------|
| `slug` | yes | kebab-case, unique. Used in URLs, view names and config keys. |
| `provider` | yes | Must extend `App\Plugins\PluginServiceProvider`. |
| `namespace` | local plugins | PSR-4 namespace for the plugin's `src/` folder. Composer packages use their own autoload instead. |
| `name`, `version`, `description`, `author` | no | Shown on the Plugins page. |
| `settings` | no | Admin-editable settings (see below). |
| `capabilities` | if it uses those hooks | What the plugin does to the site, shown on the Plugins page and confirmed when it is turned on (see [Capabilities](#capabilities)). |
| `requires` | no | What the plugin needs before it can be turned on. `trooptracker` is the plugin API range it works with (the app's is `App\Plugins\PluginApi::VERSION`, currently 1.0.0); also `php`, `ext-*` and Composer packages, each with a Composer version constraint, and `plugins`, other plugins' slugs with the versions it works with. A plugin folder's `composer.json` `require` list is checked the same way. |

### Capabilities

A plugin lists in `capabilities` the hooks that change what the site does, so an administrator can see that before turning it on. The Plugins page shows them under the plugin, and Enable asks first. A hook the plugin did not list refuses to run, and the plugin fails to load with a log error naming the missing capability.

| Capability | Hooks | Shown as |
|------------|-------|----------|
| `middleware` | `addWebMiddleware` | runs on every page request |
| `events` | `listen`, `beforeMessage`, `afterMessage` | reacts when things happen |
| `forms` | `extendForm`, `addPermissionRequestExtension` | adds fields to core forms and permission requests |
| `login` | `addLoginProvider` | adds a way to sign in |
| `donations` | `addDonationSource` | adds donations from another system |
| `club-data` | `addClubSynchronizer`, `setSheetSource` | changes club rosters and costumes |
| `schedule` | `schedule` | runs scheduled tasks |

Slots, System Check rows, notification categories and settings need no capability. This is disclosure and a guardrail, not a sandbox: a plugin is PHP code running with the app's full access, so only install plugins you trust.

### Plugin pages

Pages in `routes/web.php` need a signed-in, verified, active trooper; `routes/admin.php` pages (under `/plugins/<slug>/admin`) also need an administrator; only `routes/public.php` pages are open to anyone. Check finer permissions in the controller with a policy. Routes a plugin registers itself in its provider (such as XenForo's account pages) set their own middleware.

### The service provider

Override `registerPlugin()` and `bootPlugin()`. `register()` and `boot()` are final so the loader can catch errors and load the conventional folders for you.

```php
namespace Acme\Hello;

use App\Events\EventPosted;
use App\Messages\Faq\Commands\CreateFaqSection;
use App\Plugins\PluginServiceProvider;

class HelloServiceProvider extends PluginServiceProvider
{
    protected function registerPlugin(): void
    {
        $this->app->bind(Greeter::class, fn () => new Greeter($this->setting('greeting')));
    }

    public function bootPlugin(): void
    {
        // App events
        $this->listen(EventPosted::class, AnnounceEvent::class);

        // Hyperdrive message hooks
        $this->afterMessage(CreateFaqSection::class, function ($message, $section): void
        {
            logger()->info("FAQ section {$section->label} was created");
        });

        // UI slots
        $this->addToSlot('navbar.links', 'hello::nav-link');

        // Anything a normal Laravel provider can do
        $this->commands([SayHelloCommand::class]);
    }
}
```

Inside a plugin, follow the same conventions as core: Hyperdrive messages for business logic, invokable controllers, `declare(strict_types=1)`, and tests.

---

## Hooks

### App events

| Event | Fired when |
|-------|-----------|
| `App\Events\EventPosted` | An event is posted and troopers have been notified (created open, or a draft is opened). |
| `App\Events\EventDetailsChanged` | An event's name, times, venue, address or comments change. Has `$changed_fields`. |
| `App\Events\EventClosed` | `tracker:close-events` closes an event that has ended. |
| `App\Events\TrooperSignedUp` | A trooper signs up for a shift, or signs back up after being removed. Has `$signup` (an `EventTrooper`). |
| `App\Events\TrooperSignupStatusChanged` | A signup's status changes afterwards (going, stand-by, cancelled, attended...). Has `$signup` and `$previous_status`. |
| `App\Events\TrooperMembershipApproved` | Command staff approve a trooper's membership. Has `$trooper`. |
| `App\Events\TrooperRequestApproved` | Command staff approve a request to join an organization. Has `$trooper_request`. |
| `App\Events\TrooperPermissionsApproved` | Command staff approve a role or moderator change. Has `$permission_request`. |
| `App\Events\AwardGranted` | An award is given to a trooper. Has `$award_trooper`. |
| `App\Events\AwardRevoked` | An award is taken back. Has the soft-deleted `$award_trooper`. |

All of these fire after the database transaction commits, so a listener never sees work that is later rolled back.

`App\Events\EventWatchChanging` is different: it fires (with `Event::until()`) just before a trooper starts or stops watching an event, and has `$event`, `$trooper` and `$watching`. Return `false` from a listener to stop the change, and flash the reason. The XenForo plugin uses it to keep the forum thread subscription in step.

Plugins can also observe core models directly (`Event::observe(MyObserver::class)` in `bootPlugin`), for changes no app event covers.

Listeners registered with `$this->listen()` run in the same process as the code that fired the event. If one throws, the error is logged and reported and the event carries on without it, so a plugin failure never breaks the core flow; for `EventWatchChanging` a failing listener does not stop the change. Make a listener `ShouldQueue` when it calls something slow or flaky, so the queue retries it. More events are easy to add: create the class in `app/Events/` and dispatch it where the thing happens.

### Hyperdrive message hooks

Every Hyperdrive message fires `Hyperdrive\Events\MessageHandling` before `handle()` and `Hyperdrive\Events\MessageHandled` after it returns (after the commit for transactional messages). `beforeMessage()` and `afterMessage()` filter these by message class; pass `Hyperdrive\Message::class` to see every message. These callbacks are not isolated: an error in one stops the message, like an error in the message itself. Code that still runs through the legacy MagicBus has no hooks; it gains them as it moves to Hyperdrive.

### UI slots

Core Blade views render slots with `@pluginSlot('name')`. A slot entry is a view, rendered with the host view's variables plus the entry's `$data`. Pass `when` to show it conditionally, and `order` to sort it.

```php
$this->addToSlot(
    'events.display.sections',
    'hello::event-panel',
    data: fn (array $context) => ['count' => Greeting::forEvent($context['event'])->count()],
    when: fn (array $context) => auth()->check(),
    order: 50,
);
```

| Slot | Where | Variables |
|------|-------|-----------|
| `navbar.links` | Main navbar, before Help. Render `<x-nav-link>` items. | |
| `navbar.member-links` | Main navbar, before Command Staff; signed-in troopers who aren't denied or pending only. Render `<x-nav-link>` items. | |
| `admin.dashboard.cards` | Command Staff dashboard. Render `<x-dashboard-card>` items. | |
| `events.display.sections` | Event page, below the shifts. | `$event` |
| `events.display.discussion` | Event page, at the bottom, below the photos. | `$event` |
| `events.display.share-buttons` | Event page, share button group, before the watch toggle. Render buttons. | `$event` |
| `admin.events.form.fields` | Event create and edit forms, Miscellaneous section. Pair with `extendForm(PluginForms::EVENT_UPDATE)`. | `$event` |
| `admin.organizations.update.fields` | Organization edit form, inside the `<form>`. Pair with `extendForm`. | `$organization` |
| `service-records.trooper.badges` | Service record, under the trooper's name. | `$trooper` |
| `service-records.trooper.actions` | Service record, in the button group at the top right. Render buttons. | `$trooper` |
| `service-records.trooper.donations` | Service record, Donations tab, above the local donation records. | `$trooper` |

Svelte pages have slots too. Their names are listed in `App\Plugins\PluginSlots::SVELTE_SLOTS`; the server renders them to HTML on every Inertia request and the page shows them with `<PluginSlot name="..." />` (`resources/svelte/lib/components/PluginSlot.svelte`). The views get `$request` and `$actor` (the signed-in trooper) instead of page variables. The markup is inserted as plain HTML, so `<script>` tags in it do not run; use links and forms.

| Svelte slot | Where |
|-------------|-------|
| `account.profile.sections` | Account page, Profile tab, below the details. |
| `account.pending.sections` | Pending Membership page, below the notice. |
| `admin.troopers.approvals.sections` | Trooper Approvals page, below the requests. |

To add a slot to another Svelte page, add its name to `SVELTE_SLOTS` and drop `<PluginSlot name="..." />` where it should appear.

#### Page slots

Every page has four page slots, with no core edit needed:

| Page slot | Where |
|-----------|-------|
| `page.head` | Inside `<head>`, after the stylesheets. On Svelte pages, only on the first full page load. |
| `page.top` | Above the page content, below flash messages. |
| `page.bottom` | Below the page content. |
| `page.scripts` | End of `<body>`. On Svelte pages, only on the first full page load. |

`page.top` alone shows on every page. Add a route name after a colon to target pages: `page.top:events.display` shows only on the event page, and `page.top:events.*` on every route whose name starts with `events.`.

```php
$this->addToSlot('page.bottom:events.display', 'hello::event-footer');
$this->addToSlot('page.head', 'hello::styles');
```

On Blade pages the views get the page's variables. On Svelte pages, `page.top` and `page.bottom` come with each page change through `plugin_slots` and get `$request` and `$actor`, as other Svelte slots do. Find a page's route name with `php artisan route:list`.

A slot view that throws is logged and left out of the page.

Views can also check whether a plugin is on: `@pluginEnabled('discord') ... @endpluginEnabled`.

### Form fields

To add an input to a core form, render it through the form's slot and register its rules and save step with `extendForm()`. Core merges the rules into its form request, and calls `saved` right after it saves, in the same database transaction, so a failure rolls both back.

```php
$this->addToSlot('admin.organizations.update.fields', 'hello::org-field', fn (array $c) => [
    'value' => HelloSetting::for($c['organization']->id),
]);

$this->extendForm(PluginForms::ORGANIZATION_UPDATE,
    rules: ['hello_value' => ['nullable', 'string', 'max:64']],
    saved: fn (Organization $organization, array $validated) => HelloSetting::store($organization->id, $validated['hello_value'] ?? null),
);
```

Forms that support this are listed as constants on `App\Plugins\PluginForms`: the organization edit form (`ORGANIZATION_UPDATE`) and the event create and edit forms (`EVENT_UPDATE`, saved by the `UpdateEvent` message; `saved` gets the event and the validated input, so check your own keys are present). Give your inputs names that are not event or organization columns, such as a `hello_` prefix.

### System Check rows

`$this->addSystemCheck('Hello API key', fn () => filled($this->setting('api_key')))` adds a row to Command Staff > System Check under "Plugins". Return `true` (pass), `false` (warn) or a `SystemCheckResult`. The Plugins group also shows a failure for any enabled plugin that did not load.

### Webhooks

Routes under `/webhooks/` skip the CSRF check, so a plugin can receive webhooks there. Check the sender yourself (a shared secret header, a signature).

### Web middleware

`$this->addWebMiddleware(EnsureSomething::class)` runs a middleware on every web request, after core's own. Skip routes that must stay open (login, logout, webhooks) inside the middleware. If the middleware throws before passing the request on, the error is logged and the request carries on without it, so a plugin outage (for example the forum being down) can't take every page down. Responses it throws on purpose, such as `abort(403)` or a redirect, still apply, and errors from later in the request are not caught.

### Scheduled tasks

```php
$this->schedule(function (Schedule $schedule): void {
    $schedule->command(SyncUsersCommand::class)->hourly();
});
```

adds tasks to Laravel's scheduler. They run only while the plugin is enabled.

### Donation sources

Donations recorded somewhere else, such as paid forum subscriptions, can count toward a trooper's donation months, total donated and donor milestones. Implement `App\Contracts\DonationSource` and register it with `$this->addDonationSource(MySource::class)`.

- `summariesFor(array $trooper_ids)` is called by the achievement recalculation, one chunk of troopers at a time. Return `App\Support\DonationSummary` objects keyed by trooper ID (`month_keys` as `'Y-m' => true`, `total`, `is_active`). A fresh instance is made for each run, so it can fetch once and reuse the result for every chunk.
- `summaryFor(Trooper $trooper)` gives the live numbers on one trooper's service record. `is_active` shows the active donor badge.
- `monthTotal()` is everything donated this calendar month, for the support goal widget. Return null when it couldn't be fetched, so the widget doesn't cache a passing outage. When the sources total more than zero, the widget shows their total instead of local donation records.

Months from every source and from local donation records are merged, so a month counts once; totals add up.

### Permission requests

The Request Permissions page can ask for more than a role change, such as forum user groups. Implement `App\Contracts\PermissionRequestExtension` and register it with `$this->addPermissionRequestExtension(MyRequests::class)`. Each extension has a `key()` and:

- `formSection($trooper)` returns the HTML for its part of the form, or `''` to show nothing. Name its inputs `extensions[<key>][<field>]` (or `[<field>][]` for a list); the page sends them back on submit.
- `rules()` gives Laravel rules per field, without the `extensions.<key>.` prefix, and `validate($validator, $trooper, $input)` runs any cross-field checks. Add errors as `extensions.<key>.<field>`.
- `requestsChange($input)` says whether the input asks for anything, so a request can be just the extension's change.
- `store($permission_request, $input)` saves the request's part in the plugin's own table.
- `summary($permission_request)` returns label => value lines for the pending request list and the email.
- `approve($permission_request)` applies it. It runs inside the approval's transaction; throw `App\Exceptions\PermissionApprovalException` to roll back the whole approval and show the message.

### Login providers

Troopers can sign in through another site, such as a forum. Core has Google built in. A plugin adds one by implementing `App\Contracts\LoginProvider` and calling `$this->addLoginProvider(MyLogin::class)`, and it registers a Socialite driver named after the provider's `key()` (`Socialite::extend('myforum', ...)`).

- `key()` is the driver name, the `{provider}` in `/oauth/{provider}` and `/oauth/{provider}/callback`, and the value stored in `oauth_logins.provider`. Lowercase letters, digits, `-` and `_`.
- `name()` and `iconUrl()` make the "Login with …" and "Sign Up with …" buttons. A provider shows only while `isConfigured()` is true.
- `isRequired()`: while a configured provider is required, it is the only way in. Email/password login and sign-up, and every other provider, are turned off, and the login page says "You must use {name} to login."
- `refusal($socialite_user)` returns a message to turn someone away (a ban, for example), or null.
- `syncTrooper($trooper, $socialite_user)` runs after a successful sign-in, for example to copy the email address.
- `missingEmailRoute()` is where someone whose account has no email address is sent.

### Notification categories

`$this->addNotificationCategory('forum_reply', 'Forum Reply')` adds a row to the account page's notification preferences, which troopers can turn on or off per channel like core's own. Use the same value as the notification's `$notification_category` (notifications extend `App\Notifications\BaseNotification`). Pass `for_command_staff: true` to show it to administrators and moderators only, or `administrator_only: true` for administrators only.

Plugin pages a trooper must reach before finishing account setup, such as a page to link their account, opt in with `->defaults(TrooperSetupRequiredMiddleware::ALLOWED, true)` on the route.

### Club synchronizers

A club synchronizer keeps one club's roster and costumes in step with the club's own records, and can also check a member ID at sign-up. Administrators pick one per club in the **Roster sync** field on the organization page, and `tracker:synchronize-organizations` runs them weekly. The built-in clubs (501st Legion, Rebel Legion, Mandalorian Mercs, Dark Empire, Droid Builders, Saber Guild) are registered the same way a plugin's are, in `App\Services\Synchronizers\BuiltInClubSynchronizers`.

Register one with `$this->addClubSynchronizer('my-club', 'My Club', MyClubSync::class)`. The key is what the club stores, so keep it stable once clubs use it; a key that is already taken stops the plugin loading. The class gets the club as its `organization` constructor argument and implements one or both of:

- `App\Contracts\SynchronizerInterface`: `run()` does the sync.
- `App\Contracts\MemberLookupSource`: `lookupMember()` returns an `App\Contracts\MemberLookupInterface` for sign-up, or `null`.

Extending `App\Services\Synchronizers\BaseOrganizationService` gives both, plus helpers for reading sheets through the sheet source, matching troopers and recording costumes. A synchronizer can also read anything else, such as a club's own API.

When the plugin is off, its clubs keep their choice. The sync skips them with a warning, sign-up has no lookup, and the organization page shows the choice as not available.

### Sheet source

The club synchronizers (`tracker:synchronize-organizations`) and the sign-up member lookups read roster spreadsheets through `App\Contracts\SheetSource`. Its `rows($organization, $sheet_name)` returns every row of one sheet, header included, or `null` when the organization has no spreadsheet; throw `App\Exceptions\SheetUnavailableException` when it can't be read, so the run is skipped instead of treated as empty. Register it with `$this->setSheetSource(MySheets::class)`. A site has one source; without one, the syncs that need a sheet read nothing.

### Settings

Each entry in `settings` becomes a field on the plugin's Settings page. Types: `text`, `url`, `email`, `number`, `boolean`, `secret`. Values are validated by type, and stored encrypted in `tt_plugins.settings`. A `secret` is never shown back; leaving it empty keeps the saved value. Read settings with `$this->setting('key')` in the provider, or `app(PluginManager::class)->setting('slug', 'key')` elsewhere.

### When the plugin is enabled

Override `pluginEnabled()` in the provider to run something each time the plugin is turned on (from the Plugins page or `plugins:enable`), after its migrations and boot. A failure there is logged and does not stop the plugin from being enabled. The XenForo plugin uses it to copy its `.env` values onto its settings page.

### When plugin code fails

Plugin code that only adds to a page or reacts to something is isolated: a failure is logged and reported, and the rest of the request carries on. That covers loading and booting the plugin, slots, app event listeners, web middleware, System Check rows, `pluginEnabled()`, donation sources on the service record and support pages, and permission request sections and summaries. Scheduled tasks are isolated by the scheduler.

Plugin code that is part of a change still stops the change when it fails, so nothing is half saved: `extendForm()` save callbacks, `beforeMessage()` / `afterMessage()`, permission request validation, storing and approval, donation sources while a trooper's rank is recalculated, and club synchronizers (each club's sync fails on its own).

---

## Example: the Discord plugin

`tracker-app/plugins/discord` announces newly posted events in Discord. It used to be core code; core no longer knows Discord exists. It shows the full pattern:

- a `secret` setting with a fallback to `DISCORD_WEBHOOK_URL` in `config/config.php`
- a bound service (`DiscordNotifier`) and a listener on `EventPosted`
- its own table (`tt_discord_organization_mentions`), created by a plugin migration
- a field on the organization edit form (`admin.organizations.update.fields` slot + `extendForm`)
- a System Check row (`addSystemCheck`)
- a plugin migration that copies data out of a core column without changing core tables

### Turning Discord on

Discord is off until you enable it. Sites that used Discord before it became a plugin must do this once after deploying:

```bash
php artisan plugins:enable discord
```

or use **Command Staff > Plugins > Enable**.

### The organization mentions move

Each organization's Discord mention used to live in core's `tt_organizations.discord_mention`. The move happens in two steps, each owned by whoever owns the data:

1. **The plugin copies.** Enabling it runs its migration, which creates `tt_discord_organization_mentions` and copies every non-empty value into it. The plugin never changes core tables, and core no longer reads or writes the old column. Uninstalling drops only the plugin's table; enabling again copies from the old column once more.
2. **Core drops the column, in a later release.** Once production has the plugin enabled, one ordinary core migration drops `tt_organizations.discord_mention`, with the regenerated `Organization` model committed alongside it. Before dropping, it copies any value the plugin table is missing. If a site has mentions but never enabled the plugin, it refuses to run rather than lose them.

A fresh database seeded with the Florida Garrison data no longer gets mentions from the core seeder. With the plugin enabled, seed them from the plugin:

```bash
php artisan db:seed --class="TroopTracker\Plugins\Discord\Database\Seeders\FloridaGarrisonMentionSeeder"
```


---

## The XenForo plugin

`tracker-app/plugins/xenforo` holds the whole XenForo forum integration. Core code no longer refers to XenForo, apart from the old columns a later core migration drops (see below) and the FAQ text. It has:

- the settings page: forum URL and name, API key and user, OAuth client, webhook secret, and **Require a linked forum account**
- the XenForo API client (`TroopTracker\Plugins\Xenforo\Services\XenforoService`) and its config, `config("plugins.xenforo.*")`
- the Forum link in the navbar (`navbar.member-links` slot)
- the user sync (`xenforo:sync-users`, hourly, and `xenforo:sync-user`)
- the account link pages (`/account/xenforo`, `/account/xenforo/required`) and the two checks that run on every request while a linked account is required: send unlinked troopers to the link page, and log out banned forum users
- XenForo login: the login provider (`XenforoLoginProvider`, key `xenforo`) and its Socialite driver, including the ban check at sign-in, keeping the trooper's email in step with the forum, and **Require a linked forum account** making the forum the only way to sign in
- event forum threads: a thread is opened when an event is posted (unless "Create XenForo Thread" is set to No on the event form), kept up to date when the event or its roster changes, moved to the archive forum when the event closes, and deleted with the event
- the event page's Forum Comments section, reply box (`events.forum-reply-htmx`), forum attachments (`events.forum-attachment`), the View Forum Post button, and forum smilies in the event comments editor
- watching an event also watches its forum thread
- the forum webhook (`/webhooks/xenforo`) and the "new forum reply" and "notify command staff" notifications, with their two notification preference categories (`event_forum_post`, `forum_post_command_staff`; stored preferences keep working)
- each organization's forum settings (event forum, archive forum, and the active, reserve and retired user groups), on the organization edit form; stored in `tt_xenforo_organization_forums`, copied from the old `tt_organizations` columns on enable. `FloridaGarrisonForumSeeder` seeds the Florida Garrison values
- forum groups on the Request Permissions page (a permission request extension, key `xenforo`); requested groups are stored in `tt_xenforo_permission_request_groups`, copied from the old `tt_permission_requests.forum_groups_to_add` and `forum_groups_to_remove` columns on enable
- forum upgrades as a donation source (donation months, total donated, donor milestones, the active donor badge and the support goal widget's monthly total), plus the service record's forum group banners, Forum Profile button and upgrade history table

**Require a linked forum account** only applies while the plugin is enabled. With the plugin off, the site runs without XenForo: the XenForo login button is hidden, email and Google login work, and nobody is sent to the link page.

Settings saved on its page win over the matching `.env` values (`XENFORO_*`, `TRACKER_REQUIRE_XENFORO`), which stay as the fallback until saved. Enabling the plugin copies those `.env` values onto the settings page, skipping anything already saved, so the `.env` lines can be removed afterwards. Run `php artisan xenforo:import-env` to copy them again (for example after editing `.env`), or `xenforo:import-env --force` to replace saved settings too. The `.env` names haven't changed; they are now read by the plugin's `config/config.php` instead of core's `config/services.php` and `config/tracker.php`. `XenforoTestThreadSeeder` (posts a test thread) moved to the plugin too:

```bash
php artisan db:seed --class="TroopTracker\Plugins\Xenforo\Database\Seeders\XenforoTestThreadSeeder"
```

Each event's thread lives in the plugin's `tt_xenforo_event_threads` table. Enabling the plugin copies `tt_events.thread_id`, `post_id` and `create_forum_thread` there; core no longer reads those columns, and a later core migration drops them. The Florida Garrison legacy event import still writes the old columns, so run it before enabling the plugin.

Sites that use XenForo must enable the plugin right after deploying. Until then the user sync stops and a linked forum account is not required:

```bash
php artisan plugins:enable xenforo
```

---

## The Google Sheets plugin

`tracker-app/plugins/google-sheets` is the sheet source for the clubs whose rosters are Google Sheets (Rebel Legion, Droid Builders, Saber Guild and Mandalorian Mercs lookups). It has:

- the Google Sheets API client, with retries on transient Google errors
- the **Google Sync Sheet ID** field on a club's organization form, stored in `tt_google_sheets_organization_sheets` (copied from the old `tt_organizations.sync_sheet_id` column on enable; a later core migration drops the column)
- the **Service account credentials file** setting, `google-credentials.json` in `tracker-app` by default
- a System Check row for the credentials file
- `ClubSheetsSeeder`, which stores the known clubs' sheet IDs:

```bash
php artisan db:seed --class="TroopTracker\Plugins\GoogleSheets\Database\Seeders\ClubSheetsSeeder"
```

The plugin declares `google/apiclient` in its own `composer.json`; the app does not require it. A site that uses it adds `TRACKER_COMPOSER_PLUGINS="trooptracker/google-sheets"` to its `.env` so `deploy.sh` installs it on every deploy (or runs `composer require trooptracker/google-sheets` itself), then enables it. Without that, enabling it is refused with "needs the Composer package google/apiclient ^2.18", and its tests are skipped. CI installs it before running the tests.

Google Maps geocoding of event addresses stays in core (`GoogleService`, `GOOGLE_MAPS_API_KEY`).

Sites that sync clubs from Google Sheets must install and enable the plugin, or those syncs read nothing:

```bash
# .env, then deploy
TRACKER_COMPOSER_PLUGINS="trooptracker/google-sheets"
php artisan plugins:enable google-sheets
```

---

---

## Testing plugins

A plugin's tests live in its own `tests` folder, such as `plugins/xenforo/tests`, under the namespace `<plugin namespace>\Tests` (for example `TroopTracker\Plugins\Xenforo\Tests`). `tests/bootstrap.php` maps that namespace, and the plugin's `src` classes, for every `plugins/*/plugin.json`, so they load before the plugin is enabled. `phpunit.xml` has a `Plugins` suite that picks them up, and `php artisan test` runs them with the rest:

```bash
php artisan test --testsuite=Plugins
php artisan test plugins/xenforo/tests
```

Plugin tests extend core's `Tests\TestCase` and can use its helpers and fixtures. Core tests stay in `tracker-app/tests` and must not depend on a real plugin. `Tests\Feature\Plugins\UsesFixturePlugins` points the plugin manager at `tests/Fixtures/plugins`, which holds a small `hello` plugin that uses every hook. To test a real plugin, load it in `setUp()`:

```php
$plugins = app(PluginManager::class);
$plugins->load($plugins->findOrFail('discord'));
```
