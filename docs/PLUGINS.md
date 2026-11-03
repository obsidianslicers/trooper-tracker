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
```

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

The zip holds the plugin folder: `plugin.json` either at the top of the zip or inside one top folder (`greeter/plugin.json`). A local plugin's `namespace` is required, as for any local plugin. Composer dependencies cannot be installed from the browser, so a plugin that needs other packages has to be installed with Composer.

Before anything is written, the upload is refused if it:

- is not a zip, or is bigger than the size limit, before or after unzipping, or holds too many files
- has an absolute path, a `..` segment, a backslash or a symbolic link
- has no valid `plugin.json`, or has files outside the plugin folder
- uses a slug that is already installed (uninstall the old one first)

Files are unpacked into a temporary folder and moved into place only once every file is written. The plugin is installed **disabled**, so none of its code runs until an administrator enables it. Each upload is written to the log with the administrator's trooper ID.

Uploaded plugins go to `storage/app/plugins`, outside the git checkout, so a deploy (`git reset --hard`) never removes or overwrites them. The Plugins page lists them with the source "Uploaded". To update one, uninstall it and upload the new version; uninstalling deletes its data.

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
├── routes/web.php                 # optional, under /plugins/hello, named plugins.hello.*
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
| `App\Events\TrooperSignedUp` | A trooper signs up for a shift, or signs back up after being removed. Has `$signup` (an `EventTrooper`). |
| `App\Events\TrooperSignupStatusChanged` | A signup's status changes afterwards (going, stand-by, cancelled, attended...). Has `$signup` and `$previous_status`. |
| `App\Events\TrooperMembershipApproved` | Command staff approve a trooper's membership. Has `$trooper`. |
| `App\Events\TrooperRequestApproved` | Command staff approve a request to join an organization. Has `$trooper_request`. |
| `App\Events\TrooperPermissionsApproved` | Command staff approve a role or moderator change. Has `$permission_request`. |
| `App\Events\AwardGranted` | An award is given to a trooper. Has `$award_trooper`. |
| `App\Events\AwardRevoked` | An award is taken back. Has the soft-deleted `$award_trooper`. |

All of these fire after the database transaction commits, so a listener never sees work that is later rolled back.

Listeners run in the same process as the code that fired the event. Catch and log your own errors (see the Discord plugin's listener) so a failure in your plugin does not break the core flow, or make the listener `ShouldQueue`. More events are easy to add: create the class in `app/Events/` and dispatch it where the thing happens.

### Hyperdrive message hooks

Every Hyperdrive message fires `Hyperdrive\Events\MessageHandling` before `handle()` and `Hyperdrive\Events\MessageHandled` after it returns (after the commit for transactional messages). `beforeMessage()` and `afterMessage()` filter these by message class; pass `Hyperdrive\Message::class` to see every message. Code that still runs through the legacy MagicBus has no hooks; it gains them as it moves to Hyperdrive.

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
| `admin.dashboard.cards` | Command Staff dashboard. Render `<x-dashboard-card>` items. | |
| `events.display.sections` | Event page, below the shifts. | `$event` |
| `admin.organizations.update.fields` | Organization edit form, inside the `<form>`. Pair with `extendForm`. | `$organization` |

Svelte pages have slots too. Their names are listed in `App\Plugins\PluginSlots::SVELTE_SLOTS`; the server renders them to HTML on every Inertia request and the page shows them with `<PluginSlot name="..." />` (`resources/svelte/lib/components/PluginSlot.svelte`). The views get `$request` and `$actor` (the signed-in trooper) instead of page variables. The markup is inserted as plain HTML, so `<script>` tags in it do not run; use links and forms.

| Svelte slot | Where |
|-------------|-------|
| `account.profile.sections` | Account page, Profile tab, below the details. |
| `account.pending.sections` | Pending Membership page, below the notice. |
| `admin.troopers.approvals.sections` | Trooper Approvals page, below the requests. |

To add a slot to another Svelte page, add its name to `SVELTE_SLOTS` and drop `<PluginSlot name="..." />` where it should appear.

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

Forms that support this are listed as constants on `App\Plugins\PluginForms`. Today that is the organization edit form.

### System Check rows

`$this->addSystemCheck('Hello API key', fn () => filled($this->setting('api_key')))` adds a row to Command Staff > System Check under "Plugins". Return `true` (pass), `false` (warn) or a `SystemCheckResult`. The Plugins group also shows a failure for any enabled plugin that did not load.

### Settings

Each entry in `settings` becomes a field on the plugin's Settings page. Types: `text`, `url`, `email`, `number`, `boolean`, `secret`. Values are validated by type, and stored encrypted in `tt_plugins.settings`. A `secret` is never shown back; leaving it empty keeps the saved value. Read settings with `$this->setting('key')` in the provider, or `app(PluginManager::class)->setting('slug', 'key')` elsewhere.

---

## Example: the Discord plugin

`tracker-app/plugins/discord` announces newly posted events in Discord. It used to be core code; core no longer knows Discord exists. It shows the full pattern:

- a `secret` setting with a fallback to `DISCORD_WEBHOOK_URL` in `config/config.php`
- a bound service (`DiscordNotifier`) and a listener on `EventPosted`
- its own table (`tt_discord_organization_mentions`), created by a plugin migration
- a field on the organization edit form (`admin.organizations.update.fields` slot + `extendForm`)
- a System Check row (`addSystemCheck`) and an Artisan command

### Turning Discord on

Discord is off until you enable it. Sites that used Discord before it became a plugin must do this once after deploying:

```bash
php artisan plugins:enable discord
php artisan discord:verify-mentions
```

or use **Command Staff > Plugins > Enable**, then run the verify command.

### The organization mentions move

Each organization's Discord mention used to live in `tt_organizations.discord_mention`. Enabling the plugin runs its migration, which **copies** every non-empty value into `tt_discord_organization_mentions`. The old column is left untouched, and core no longer reads or writes it.

Uninstalling the Discord plugin drops its table, so mentions edited after the copy are lost; the old column is never touched, and enabling again copies from it.

A fresh database seeded with the Florida Garrison data no longer gets mentions from the core seeder. With the plugin enabled, seed them from the plugin:

```bash
php artisan db:seed --class="TroopTracker\Plugins\Discord\Database\Seeders\FloridaGarrisonMentionSeeder"
```

`php artisan discord:verify-mentions` compares the two. It fails, and says not to drop the column, if any value is missing or was copied wrong. Rows an admin edited or cleared after the copy are listed as expected changes. Once it passes on production, a later core migration can drop `tt_organizations.discord_mention` (then regenerate the base models). That drop is deliberately not part of this change.

---

## Testing plugins

Tests live in `tracker-app/tests`. `Tests\Feature\Plugins\UsesFixturePlugins` points the plugin manager at `tests/Fixtures/plugins`, which holds a small `hello` plugin that uses every hook. To test a real plugin, load it in `setUp()`:

```php
$plugins = app(PluginManager::class);
$plugins->load($plugins->findOrFail('discord'));
```
