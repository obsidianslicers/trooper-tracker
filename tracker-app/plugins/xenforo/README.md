# XenForo plugin

Connects TroopTracker to a [XenForo](https://xenforo.com) forum: forum login, forum users kept in
step with troopers, a forum thread for each event with its replies on the event page, forum
notifications, forum groups on permission requests, and forum upgrades counted as donations.
Enable it if your club runs a XenForo forum. Core code no longer refers to XenForo; with the
plugin off the site runs without it (email and Google login, no forum features).

The plugin is off until an administrator enables it (**Command Staff > Plugins**, or
`php artisan plugins:enable xenforo`). See
[docs/PLUGINS.md](../../../docs/PLUGINS.md#the-xenforo-plugin) for how it moved out of core, and
[docs/XENFORO_OAUTH.md](../../../docs/XENFORO_OAUTH.md) for the full forum-side setup.

## Configuration

### On the forum

- Install the TroopTracker XenForo add-on
  ([obsidianslicers/troop-tracker-xenforo-addons](https://github.com/obsidianslicers/troop-tracker-xenforo-addons),
  XenForo 2.2+). The upgrade stats and user groups API features rely on it.
- Create an OAuth client (for login) and a super user API key (for everything else).
- For reply notifications, add a webhook to `{APP_URL}/webhooks/xenforo`, content type
  `application/json`, event **Post > Insert**, with the same secret as the plugin's
  **Webhook secret**.
- Custom user fields `trackerid`, `tkid`, `fullname` and `organizations`, which the user sync
  fills in.

### On the plugin's settings page

Each setting falls back to its `.env` value until it is saved. Enabling the plugin copies the
`.env` values onto the settings page (skipping anything already saved), so the `.env` lines can be
removed afterwards.

| Setting | Type | Fallback |
|---------|------|----------|
| **Forum URL** (`base_url`) | url | `XENFORO_BASE_URL` |
| **Forum name** (`name`), shown on the login button | text | `XENFORO_NAME` |
| **API key** (`api_key`), a super user key | secret | `XENFORO_API_KEY` |
| **API user ID** (`api_user`), the user the API acts as | text | `XENFORO_API_USER` |
| **OAuth client ID** (`client_id`) | text | `XENFORO_CLIENT_ID` |
| **OAuth client secret** (`client_secret`) | secret | `XENFORO_CLIENT_SECRET` |
| **OAuth redirect URL** (`redirect`) | url | `XENFORO_REDIRECT_URI` |
| **Webhook secret** (`webhook_secret`) | secret | `XENFORO_WEBHOOK_SECRET` |
| **Require a linked forum account** (`require_xenforo`) | boolean | `TRACKER_REQUIRE_XENFORO` |

The OAuth endpoint paths can be changed with `XENFORO_AUTHORIZE_PATH`, `XENFORO_TOKEN_PATH` and
`XENFORO_ME_PATH` (defaults are the XenForo 2.3 paths).

### On TroopTracker

- Each organization's edit page has forum fields: event forum, archive forum, and the active,
  reserve and retired user groups.
- Each event type's Update page has a Forum section (event forum and archive forum), which wins
  over the hosting organization's forum of the same kind.

## What it adds

**Login and accounts**

- XenForo login (login provider `xenforo` and its Socialite driver), with a ban check at sign-in
  and the trooper's email kept in step with the forum.
- `/account/xenforo` and `/account/xenforo/required`, where troopers link their forum account.
- Two web middleware: while **Require a linked forum account** is on, unlinked troopers are sent
  to the link page (and the forum becomes the only way to sign in); banned forum users are logged
  out.
- A Forum link in the navbar (`navbar.member-links` slot).

**User sync**

- `php artisan xenforo:sync-users [--chunk=100]`, scheduled hourly, and
  `php artisan xenforo:sync-user {trooper} [--dry-run]` for one trooper. They set each linked
  forum user's custom fields and the secondary user groups managed through the organizations'
  forum settings. Pending and denied troopers are skipped.

**Event threads**

- A thread is opened when an event is posted (unless **Create XenForo Thread** is No on the event
  form), kept up to date when the event or its roster changes, moved to the archive forum when
  the event closes, and deleted with the event. Titles start with the event date
  (`2026/03/14 Sat - Hospital Visit`) so a forum sorted by title lists events in date order.
- On the event page: Forum Comments, a reply box (`POST /events/forum-reply/{event}`), forum
  attachments (`GET /events/forum-attachments/{id}`, signed), a View Forum Post button, and forum
  smilies in the comments editor.
- Watching an event also watches its forum thread.

**Notifications**

- `POST /webhooks/xenforo` receives new forum posts (checked against the `xf-webhook-secret`
  header) and queues notifications to the event's watchers.
- Two notification preference categories: **Event Forum Post** (`event_forum_post`) and
  **Forum Post Command Staff** (`forum_post_command_staff`, command staff only).

**Permission requests and service records**

- Forum groups to add or remove on the Request Permissions page.
- Forum upgrades as a donation source (donation months, total donated, donor milestones, the
  active donor badge and the support goal's monthly total), plus forum group banners, a Forum
  Profile button and an upgrade history table on the service record.

**Admin route.** `POST /plugins/xenforo/admin/event-types/{event_type}/forum` saves an event
type's forums.

**Other console command.** `php artisan xenforo:import-env [--force]` copies the `.env` values
onto the settings page again (`--force` replaces saved settings).

**System Check row.** "XenForo API" passes when a forum URL and API key are set.

**Tables**

| Table | Holds |
|-------|-------|
| `tt_xenforo_event_threads` | Each event's thread and post IDs, and whether to create a thread |
| `tt_xenforo_organization_forums` | Each organization's forums and user groups |
| `tt_event_type_xenforo_forums` | Each event type's forums |
| `tt_xenforo_permission_request_groups` | Forum groups requested on a permission request |

Enabling copies any values left in the old core columns (`tt_events.thread_id`, `post_id`,
`create_forum_thread`, the organization forum columns and the permission request forum group
columns).

**Seeders**

```bash
php artisan db:seed --class="TroopTracker\Plugins\Xenforo\Database\Seeders\FloridaGarrisonForumSeeder"
php artisan db:seed --class="TroopTracker\Plugins\Xenforo\Database\Seeders\XenforoTestThreadSeeder"
```

`FloridaGarrisonForumSeeder` stores the Florida Garrison forum settings;
`XenforoTestThreadSeeder` posts a test thread.

## Limitations

- One forum per site.
- Reply notifications need the queue worker running, and only reach events that have a thread.
  A poster without a linked forum account can't be recognised, so they are notified of their
  own reply.
- **Require a linked forum account** only applies while the plugin is enabled.

## Tests

From `tracker-app`:

```bash
php artisan test plugins/xenforo/tests
```
