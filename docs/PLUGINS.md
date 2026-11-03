# Plugins

Plugins add features to TroopTracker without changing core code. A plugin can listen for app events, run code before or after Hyperdrive messages, add markup to the UI, and ship its own routes, views, migrations, config, settings and Artisan commands.

Plugins are full PHP code that runs with the same access as the app, so only install plugins you trust. They are installed by deploying them (git or Composer), never uploaded through the browser.

Every plugin is **off** until an administrator enables it. Enabling is site-wide.

---

## Managing plugins

- **Admin > Command Staff > Plugins** (administrators only) lists every plugin found, with Enable / Disable buttons and a Settings page for plugins that declare settings.
- From the command line:

```bash
php artisan plugins:list
php artisan plugins:enable <slug>    # also runs the plugin's migrations
php artisan plugins:disable <slug>   # keeps the plugin's data and settings
```

Enabling or disabling a plugin rebuilds the config and route caches if they are cached, and restarts the queue workers, so the change reaches every request and worker.

A plugin that throws while loading or booting is logged and skipped. It cannot take the site down. It shows as "Failed to load" on the Plugins page.

---

## Where plugins come from

1. **Local folder.** Any folder in `tracker-app/plugins/` that holds a `plugin.json`. Set `TRACKER_PLUGINS_PATH` to use another folder.
2. **Composer packages** of type `trooptracker-plugin` with a `plugin.json` at the package root. Composer autoloads them, so they can live in their own repository and be shared with other garrisons.

If two plugins use the same slug, the Composer package wins and the other is skipped with a log warning.

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

Svelte pages have slots too. Their names are listed in `App\Plugins\PluginSlots::SVELTE_SLOTS`; the server renders them to HTML on every Inertia request and the page shows them with `<PluginSlot name="..." />` (`resources/svelte/lib/components/PluginSlot.svelte`). The views get `$request` and `$actor` (the signed-in trooper) instead of page variables. The markup is inserted as plain HTML, so `<script>` tags in it do not run; use links and forms.

| Svelte slot | Where |
|-------------|-------|
| `account.profile.sections` | Account page, Profile tab, below the details. |
| `account.pending.sections` | Pending Membership page, below the notice. |
| `admin.troopers.approvals.sections` | Trooper Approvals page, below the requests. |

To add a slot to another Svelte page, add its name to `SVELTE_SLOTS` and drop `<PluginSlot name="..." />` where it should appear.

A slot view that throws is logged and left out of the page.

Views can also check whether a plugin is on: `@pluginEnabled('discord') ... @endpluginEnabled`.

### Settings

Each entry in `settings` becomes a field on the plugin's Settings page. Types: `text`, `url`, `email`, `number`, `boolean`, `secret`. Values are validated by type, and stored encrypted in `tt_plugins.settings`. A `secret` is never shown back; leaving it empty keeps the saved value. Read settings with `$this->setting('key')` in the provider, or `app(PluginManager::class)->setting('slug', 'key')` elsewhere.

---

## Example: the Discord plugin

`tracker-app/plugins/discord` announces newly posted events in Discord. It used to be core code. It shows the full pattern: a `secret` setting with a fallback to `DISCORD_WEBHOOK_URL` in `config/config.php`, a bound service (`DiscordNotifier`), and a listener on `EventPosted`.

Sites that already had `DISCORD_WEBHOOK_URL` set get the plugin enabled automatically by the `enable_discord_plugin_for_existing_webhooks` migration.

---

## Testing plugins

Tests live in `tracker-app/tests`. `Tests\Feature\Plugins\UsesFixturePlugins` points the plugin manager at `tests/Fixtures/plugins`, which holds a small `hello` plugin that uses every hook. To test a real plugin, load it in `setUp()`:

```php
$plugins = app(PluginManager::class);
$plugins->load($plugins->findOrFail('discord'));
```
