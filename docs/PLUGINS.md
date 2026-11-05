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

The zip holds the plugin folder: `plugin.json` either at the top of the zip or inside one top folder (`greeter/plugin.json`). A local plugin's `namespace` is required, as for any local plugin. An upload cannot install packages (see [Packages a plugin needs](#packages-a-plugin-needs)), so if the zip's `composer.json` requires anything the app doesn't already have, the upload is refused and names what is missing.

Before anything is written, the upload is refused if it:

- is not a zip, or is bigger than the size limit, before or after unzipping, or holds too many files
- has an absolute path, a `..` segment, a backslash or a symbolic link
- has no valid `plugin.json`, or has files outside the plugin folder
- uses a slug that is already installed (uninstall the old one first)

Files are unpacked into a temporary folder and moved into place only once every file is written. The plugin is installed **disabled**, so none of its code runs until an administrator enables it. Each upload is written to the log with the administrator's trooper ID.

Uploaded plugins go to `storage/app/plugins`, outside the git checkout, so a deploy (`git reset --hard`) never removes or overwrites them. The Plugins page lists them with the source "Uploaded". To update one, uninstall it and upload the new version; uninstalling deletes its data.

### Packages a plugin needs

A plugin lists the packages it needs in its own `composer.json` `require` section, and Composer installs them together with the app's packages. Composer resolves everything as one set, so a plugin can't pull in a version that clashes with the app's.

- **Composer package plugins:** `composer require vendor/plugin` installs the plugin and its packages. Commit `composer.json` and `composer.lock`; the deploy's `composer install` does the rest.
- **Plugins in this repo:** the app's `composer.json` has a path repository for `plugins/*`. A local plugin with a `composer.json` (`make:plugin --composer` writes one) is installed with `composer require trooptracker/<slug>`, which pulls in its packages and symlinks the folder into `vendor/`. It is then listed once, as a Composer package. Keep a `version` in the plugin's `composer.json` so the lock file does not depend on the branch name. A local plugin with no packages of its own needs none of this.
- **Uploaded plugins:** these can only use what the app already has. On upload, the plugin's `composer.json` `require` list is checked against the installed packages, PHP extensions and PHP version, and the zip is refused if anything is missing.

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

`$this->addWebMiddleware(EnsureSomething::class)` runs a middleware on every web request, after core's own. Skip routes that must stay open (login, logout, webhooks) inside the middleware.

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

Months from every source and from local donation records are merged, so a month counts once; totals add up.

### Settings

Each entry in `settings` becomes a field on the plugin's Settings page. Types: `text`, `url`, `email`, `number`, `boolean`, `secret`. Values are validated by type, and stored encrypted in `tt_plugins.settings`. A `secret` is never shown back; leaving it empty keeps the saved value. Read settings with `$this->setting('key')` in the provider, or `app(PluginManager::class)->setting('slug', 'key')` elsewhere.

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

`tracker-app/plugins/xenforo` is moving the XenForo forum integration out of core in phases (see the plan doc). So far it holds:

- the settings page: forum URL and name, API key and user, OAuth client, webhook secret, and **Require a linked forum account**
- the user sync (`xenforo:sync-users`, hourly, and `xenforo:sync-user`)
- the account link pages (`/account/xenforo`, `/account/xenforo/required`) and the two checks that run on every request while a linked account is required: send unlinked troopers to the link page, and log out banned forum users
- the XenForo login driver (the Socialite provider behind "Sign in with XenForo")
- event forum threads: a thread is opened when an event is posted (unless "Create XenForo Thread" is set to No on the event form), kept up to date when the event or its roster changes, moved to the archive forum when the event closes, and deleted with the event
- the event page's Forum Comments section, reply box (`events.forum-reply-htmx`), forum attachments (`events.forum-attachment`), the View Forum Post button, and forum smilies in the event comments editor
- watching an event also watches its forum thread
- the forum webhook (`/webhooks/xenforo`) and the "new forum reply" and "notify command staff" notifications
- forum upgrades as a donation source (donation months, total donated, donor milestones and the active donor badge), plus the service record's forum group banners, Forum Profile button and upgrade history table

**Require a linked forum account** only applies while the plugin is enabled. With the plugin off, the site runs without XenForo: the XenForo login button is hidden, email and Google login work, and nobody is sent to the link page.

Core's login flow still knows the name "xenforo" (the provider value stored on `oauth_logins`, the login and sign-up buttons, and the OAuth callback's XenForo-only steps). Making login providers fully pluggable is a separate, larger change.

Settings saved on its page win over the matching `.env` values (`XENFORO_*`, `TRACKER_REQUIRE_XENFORO`), which stay as the fallback until saved. The rest of XenForo (forum groups, and the XenForo API client `App\Services\Forums\XenforoService`) is still core and reads the same config, so the settings page applies to it too while the plugin is enabled.

Each event's thread lives in the plugin's `tt_xenforo_event_threads` table. Enabling the plugin copies `tt_events.thread_id`, `post_id` and `create_forum_thread` there; core no longer reads those columns, and a later core migration drops them. The Florida Garrison legacy event import still writes the old columns, so run it before enabling the plugin.

Sites that use XenForo must enable the plugin right after deploying. Until then the user sync stops and a linked forum account is not required:

```bash
php artisan plugins:enable xenforo
```

---

## Testing plugins

Tests live in `tracker-app/tests`. `Tests\Feature\Plugins\UsesFixturePlugins` points the plugin manager at `tests/Fixtures/plugins`, which holds a small `hello` plugin that uses every hook. To test a real plugin, load it in `setUp()`:

```php
$plugins = app(PluginManager::class);
$plugins->load($plugins->findOrFail('discord'));
```
