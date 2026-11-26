# Artisan Commands

All custom commands use the `tracker:`, `xenforo:` or `holonet:` namespace and are run via:

```bash
php artisan <command> [arguments] [options]
```

---

## Scheduled Commands

These commands run automatically via the scheduler defined in `routes/console.php`. All times use the timezone configured in `tracker.calendar.timezone`.

| Command | Schedule |
|---|---|
| `tracker:dispatch-queue-heartbeat` | Every minute |
| `tracker:check-supervisor-health` | Every 5 minutes |
| `auth:clear-resets` (Laravel) | Every 15 minutes |
| `xenforo:sync-users` (XenForo plugin, while enabled) | Every hour |
| `tracker:close-event-shifts` | Every hour |
| `tracker:expire-visitor-access` | Daily at 12:30 AM |
| `tracker:close-events` | Daily at 1:00 AM |
| `tracker:calculate-trooper-achievements` | Daily at 2:00 AM |
| `tracker:process-account-deletions` | Daily at 3:30 AM |
| `holonet:refresh-links --spread` (HoloNet plugin, while enabled) | Daily, starting at a random time between 4:00 and 5:00 AM |
| `tracker:send-daily-event-notifications` | Daily at 8:00 AM |
| `tracker:send-daily-milestone-notifications` | Daily at 8:00 AM |
| `tracker:send-tentative-reminders` | Daily at 8:00 AM |
| `tracker:remind-closed-event-shifts` | Daily at 9:00 AM |
| `tracker:synchronize-organizations` | Weekly, Sunday at 3:00 AM |
| `tracker:remind-stale-verifications` | Weekly, Monday at 9:00 AM |

---

## Command Reference

### `tracker:calculate-trooper-achievements`

Recalculates trooper ranks, metrics, and milestone achievements for all troopers based on their event history.

Dispatches `RecalculateTrooperRank` as a Hyperdrive message. Reports execution time and newly created milestone counts when complete.

```bash
php artisan tracker:calculate-trooper-achievements
```

Use `--without-notifications` for large backfills or schema changes that may create many historical milestones. This still creates achievement rows, skips per-milestone command-staff notifications, marks milestone notifications as handled, and prints a summary instead.

```bash
php artisan tracker:calculate-trooper-achievements --without-notifications
```

---

### `tracker:close-event-shifts`

Closes event shifts whose end time has passed and sends a completion email to all troopers with GOING status on those shifts.

```bash
php artisan tracker:close-event-shifts
```

---

### `tracker:close-events`

Closes events whose end time has passed. If XenForo integration is configured, moves each event's forum thread to the organization's archive forum.

```bash
php artisan tracker:close-events
```

---

### `tracker:expire-visitor-access`

Notifies visitor troopers whose 6-month access window has elapsed. Dispatches `NotifyExpiredVisitor` for each expired visitor. Does not modify membership status — renewal requires a separate request.

```bash
php artisan tracker:expire-visitor-access
```

---

### `tracker:fix-trooper-assignment-hierarchy`

One-time maintenance command. Sets `is_member = false` on parent-level assignments where a more-specific child assignment already exists for the same trooper, preventing duplicate membership counting.

```bash
php artisan tracker:fix-trooper-assignment-hierarchy
```

---

### Merging troopers (no command)

There is no Artisan command for merging troopers. A system owner merges two accounts on the admin **Merge Troopers** page (`/admin/troopers/merge`, `ShowMergeTroopersFormController` → `MergeTroopersController`), which queues `MergeTroopersJob`. The job calls the `MergeTroopers` message: it moves the source trooper's organizations, assignments, costumes, donations, friends, awards, event sign-ups, uploads, watches, mission acknowledgements, notifications, shares and achievements onto the target, reconciles account-level fields, sets the source's `membership_status` to `MERGED`, and notifies the administrators when done.

---

### `tracker:process-account-deletions`

Permanently anonymizes and soft-deletes trooper accounts that have been pending deletion for 30 or more days. Picks up any account where `deletion_requested_at` is set and the 30-day grace period has elapsed.

Run automatically by the scheduler. Can also be run manually to force-process any overdue accounts.

```bash
php artisan tracker:process-account-deletions
```

---

### `fabricator:generate-factories`

Generates Eloquent model factories for all models in the models directory that don't already have one. Useful during development when new models are added.

```bash
php artisan fabricator:generate-factories
```

---

### `tracker:report-translations`

Reports, per language, how many lines are translated, which keys are missing (they fall back to English) and which are orphaned (English no longer has them), plus keys code uses that the English files don't define. CI writes the Markdown form to the test job's summary. See [LOCALIZATION.md](LOCALIZATION.md).

```bash
php artisan tracker:report-translations [--markdown] [--strict]
```

| Option | Description |
|---|---|
| `--markdown` | Write a Markdown summary instead of a console table |
| `--strict` | Exit with an error when keys are undefined or orphaned (missing translations alone never fail) |

---

### `tracker:merge-costumes`

Folds a duplicate costume into the costume you want to keep, then permanently deletes the duplicate. Everything that pointed at the duplicate moves to the kept costume, including soft-deleted records:

- **Club links** (`tt_organization_costumes`): a club that only had the duplicate is repointed. A club that had both keeps its existing link (taking the duplicate's prefix only if it had none).
- **Trooper costumes** (`tt_trooper_costumes`): a trooper who only had the duplicate is moved over. A trooper who had both keeps their existing record, missing photos are filled in from the duplicate, and a display costume set to the duplicate moves to the kept record.
- **Event sign-ups** (`tt_event_troopers`): primary and backup costumes are repointed. If a sign-up ends up with the same primary and backup costume, the backup is cleared. Credited club ids are unchanged.

The command shows how many clubs, troopers, and sign-ups each costume has, then asks for confirmation. The whole merge runs in one transaction.

Administrators can do the same merge from the web at **Command Staff → Merge Costumes** (`/admin/costumes/merge`). The Handler and Command Staff costumes cannot be merged away.

```bash
php artisan tracker:merge-costumes {source_costume_id} {target_costume_id} [--force]
```

| Argument/Option | Required | Description |
|---|---|---|
| `source_costume_id` | Yes | ID of the duplicate costume to fold in and delete (may be soft-deleted) |
| `target_costume_id` | Yes | ID of the costume to keep |
| `--force` | No | Skip the confirmation prompt |

**Note:** club synchronizers match costumes by name. If the duplicate's name comes from a club sync, the next sync will create it again. In that case keep the costume whose name matches the club's feed, or fix the name in the club's data first.

**Example:**
```bash
# Merge costume 87 ("TK Stormtroper") into costume 12 ("TK Stormtrooper")
php artisan tracker:merge-costumes 87 12
```

---

### `tracker:prune-missing-uploads`

Finds event photo records (`tt_event_uploads`) whose full-size image file is no longer on disk, and optionally deletes them. These records show as broken images in the gallery and in the public API's `photos` feed.

- Legacy uploads (a bare filename) are checked in `public/images/uploads/`.
- Newer uploads (a path such as `uploads/events/12/originals/...`) are checked on the public disk (`storage/app/public`).

Without `--delete` the command only lists what it found. With `--delete` it asks for confirmation, then removes each record, its tagged troopers, and any leftover thumbnail.

Before deleting, check that the files are really gone rather than not copied over yet: a long list of legacy filenames usually means `public/images/uploads/` was not copied from the old tracker, and a long list of `uploads/events/...` paths usually means `storage/app/public` is on the wrong volume.

```bash
php artisan tracker:prune-missing-uploads [--delete] [--force]
```

| Option | Description |
|---|---|
| `--delete` | Delete the records (without it, only lists them) |
| `--force` | Skip the confirmation prompt when deleting |

---

### `tracker:mimic-trooper-permissions`

Copies the `membership_role` and all `TrooperAssignment` records from a source trooper to a target trooper. Saves a snapshot of the target's original permissions before applying the change so it can be fully reverted. Useful for testing authorization flows as a specific role without changing accounts. **Dev use only.**

A snapshot is stored at `storage/app/private/permission-mimics/trooper-{id}.json`. Only one snapshot per target trooper is kept at a time — use `--force` to overwrite, or `--revert` to restore and delete it.

```bash
php artisan tracker:mimic-trooper-permissions {target_trooper_id} [source_trooper_id] [--revert] [--force]
```

| Argument/Option | Required | Description |
|---|---|---|
| `target_trooper_id` | Yes | ID of the trooper whose permissions will be changed |
| `source_trooper_id` | Yes* | ID of the trooper to copy permissions from (*not required with `--revert`) |
| `--revert` | No | Restore the target trooper to their snapshotted permissions and delete the snapshot |
| `--force` | No | Overwrite an existing snapshot instead of aborting |

**Examples:**
```bash
# Copy permissions from trooper 1 to trooper 42 (saves snapshot first)
php artisan tracker:mimic-trooper-permissions 42 1

# Revert trooper 42 back to their original permissions
php artisan tracker:mimic-trooper-permissions 42 --revert

# Overwrite an existing snapshot and re-mimic
php artisan tracker:mimic-trooper-permissions 42 1 --force
```

---

### `tracker:stamp-deployment`

Records the deployed commit and the time into `storage/app/deploy.json`, which the public `/status` page returns as JSON (`{"commit": "...", "deployed_at": "..."}`; both are `null` until a deploy has run this). `deploy.sh` runs it as its last step; run it yourself after a manual deploy.

```bash
php artisan tracker:stamp-deployment <commit>
```

| Argument | Description |
|---|---|
| `commit` | The git hash that is now deployed |

---

### `tracker:reopen-future-shifts`

Finds all event shifts that are marked CLOSED but have not yet occurred (`shift_ends_at` is in the future) and reopens them. Any trooper attendance records incorrectly confirmed (ATTENDED or UNABLE_TO_ATTEND) are reset back to GOING.

Run with `--dry-run` first to preview what would change before applying it.

```bash
php artisan tracker:reopen-future-shifts [--dry-run]
```

| Option | Description |
|---|---|
| `--dry-run` | Preview changes without applying them |

> **Note:** This is a one-time remediation command and is not scheduled.

---

### `tracker:send-daily-event-notifications`

Sends consolidated daily email digests to troopers who have upcoming events. Queries troopers eligible for notification and dispatches `SendEventDailyNotification` for each.

```bash
php artisan tracker:send-daily-event-notifications
```

---

### `tracker:send-daily-milestone-notifications`

Sends the daily roundup of trooper milestones to the administrators and moderators in scope who opted in to them, by queueing `SendTrooperMilestoneNotificationsJob`.

```bash
php artisan tracker:send-daily-milestone-notifications
```

---

### `tracker:send-tentative-reminders`

Reminds troopers whose sign-up is still **Tentative** to confirm or cancel. Finds them with `GetTentativeEventTroopers` and sends each a `SendTentativeReminder`.

```bash
php artisan tracker:send-tentative-reminders
```

---

### `tracker:remind-closed-event-shifts`

After a shift closes, asks every trooper who meant to go whether they made it. Finds the shifts with `GetEventShiftsToRemind`, records when each was last reminded, and sends `EventShiftCompletedNotification` to each trooper who intended to go.

```bash
php artisan tracker:remind-closed-event-shifts
```

---

### `tracker:remind-stale-verifications`

Notifies system owners when troop verifications have been waiting more than 30 days (`StaleVerificationsNotification`, with the count). Does nothing when none are waiting that long.

```bash
php artisan tracker:remind-stale-verifications
```

---

### `tracker:dispatch-queue-heartbeat` / `tracker:check-supervisor-health`

Together they watch the queue worker. `tracker:dispatch-queue-heartbeat` queues `QueueWorkerHeartbeatJob` every minute; when a worker runs it, it records a heartbeat. `tracker:check-supervisor-health` checks every five minutes whether that heartbeat has gone stale and emails the administrators when the worker goes down and again when it recovers. The check only runs when `tracker.supervisor_check.email_enabled` is on and the queue isn't `sync`.

```bash
php artisan tracker:dispatch-queue-heartbeat
php artisan tracker:check-supervisor-health
```

---

### `tracker:send-test-push`

Sends a test push notification to a specific trooper. Picks a random notification from a predefined test set.

```bash
php artisan tracker:send-test-push {trooper_id} [--url=<path>]
```

| Argument/Option | Required | Description |
|---|---|---|
| `trooper_id` | Yes | ID of the trooper to receive the notification |
| `--url` | No | In-app path to open (default: `/events`) |

**Examples:**
```bash
php artisan tracker:send-test-push 42
php artisan tracker:send-test-push 42 --url=/events/details/99
```

---

### `tracker:simulate-account-deletion`

Creates a disposable dummy trooper with event sign-ups and associated records, then either requests deletion (for UI inspection) or immediately executes the full anonymization and deletion (for logic verification). **Dev use only — do not run in production.**

In default mode the command prints login credentials and a login URL so you can inspect the pending-deletion banner and test the cancel flow in a real browser. In `--execute` mode it runs the full deletion immediately and prints a pass/fail verification table.

> **Note:** Confirmation emails are queued, not sent immediately. Run `php artisan queue:work` to deliver them.

```bash
php artisan tracker:simulate-account-deletion [--execute]
```

| Option | Description |
|---|---|
| `--execute` | Skip the 30-day grace period and immediately run anonymization and deletion |

**Examples:**
```bash
# Create account + request deletion → log in to verify the banner
php artisan tracker:simulate-account-deletion

# Create account → execute deletion → verify all checks pass
php artisan tracker:simulate-account-deletion --execute
```

---

### `tracker:simulate-trooper-request`

Creates a pending club join request for a trooper and outputs the admin review URL. Dev use only.

```bash
php artisan tracker:simulate-trooper-request [trooper_id]
```

| Argument | Required | Description |
|---|---|---|
| `trooper_id` | No | ID of the trooper to submit the request for (default: random active trooper) |

**Examples:**
```bash
php artisan tracker:simulate-trooper-request
php artisan tracker:simulate-trooper-request 42
```

---

### `tracker:simulate-shift-complete`

Creates a test shift-complete scenario for a trooper and outputs confirmation URLs for both "attended" and "unable to attend" responses. Used to test the post-shift update flow without waiting for a real shift to end.

Costume is selected randomly from the trooper's approved costumes. Falls back to the Handler costume if the trooper has no approved costumes in the database.

```bash
php artisan tracker:simulate-shift-complete {trooper_id} [--expired] [--dual-costume] [--triple-costume] [--no-eligible-orgs]
```

| Argument/Option | Required | Description |
|---|---|---|
| `trooper_id` | Yes | ID of the trooper to generate the scenario for |
| `--expired` | No | Sets the shift as ended 45 days ago (outside the 30-day update window) |
| `--dual-costume` | No | Triggers the club-selection flow by setting up 2 distinct top-level parent orgs as eligible for credit. Requires the trooper to have memberships in at least 2 clubs with different top-level parent organizations. |
| `--triple-costume` | No | Same as `--dual-costume` but with 3 distinct parent orgs. Requires 3 qualifying memberships. Mutually exclusive with `--dual-costume`. |
| `--no-eligible-orgs` | No | Produces a scenario where no organizations are eligible for credit, so neither club-selection nor auto-credit-assignment fires. Cannot be combined with `--dual-costume` or `--triple-costume`. |

**Examples:**
```bash
php artisan tracker:simulate-shift-complete 42
php artisan tracker:simulate-shift-complete 42 --expired
php artisan tracker:simulate-shift-complete 42 --dual-costume
php artisan tracker:simulate-shift-complete 42 --triple-costume
php artisan tracker:simulate-shift-complete 42 --no-eligible-orgs
php artisan tracker:simulate-shift-complete 42 --expired --no-eligible-orgs
```

---

### `tracker:synchronize-organizations`

Runs the roster sync each club chose on its organization page, whether built in or from a plugin, and reports timing and success or failure per club. A club whose sync comes from a plugin that is off is skipped with a warning.

```bash
php artisan tracker:synchronize-organizations
```

---

### `xenforo:sync-user` (XenForo plugin)

Syncs a single trooper's data to XenForo. Replaces `tracker:synchronize-xenforo-user`. Available while the XenForo plugin is enabled.

```bash
php artisan xenforo:sync-user {trooper} [--dry-run]
```

| Argument / option | Required | Description |
|---|---|---|
| `trooper` | Yes | ID of the trooper to sync |
| `--dry-run` | No | Show the group changes without writing to XenForo |

**Example:**
```bash
php artisan xenforo:sync-user 42
```

---

### `holonet:refresh-links` (HoloNet plugin)

Copies troop history again from every tracker troopers have linked. A tracker that can't be reached keeps its last copy; one that no longer accepts the link has its copy removed. Available while the HoloNet plugin is enabled.

```bash
php artisan holonet:refresh-links [--spread]
```

| Option | Description |
|---|---|
| `--spread` | Wait a random time first (up to `HOLONET_NIGHTLY_JITTER` seconds, default an hour), so trackers don't all refresh at once. The nightly run uses it. |

---

### `xenforo:sync-users` (XenForo plugin)

Syncs all troopers to XenForo, processed in chunks to avoid memory issues. Replaces `tracker:synchronize-xenforo-users`, and runs hourly while the XenForo plugin is enabled.

```bash
php artisan xenforo:sync-users [--chunk=<size>]
```

| Option | Default | Description |
|---|---|---|
| `--chunk` | `100` | Number of troopers to process per chunk (minimum: 1) |

**Example:**
```bash
php artisan xenforo:sync-users --chunk=50
```

---

### `xenforo:import-env` (XenForo plugin)

Copies the `XENFORO_*` and `TRACKER_REQUIRE_XENFORO` values from `.env` onto the plugin's settings page (Command Staff > Plugins > XenForo > Settings). Settings that are already saved are kept unless `--force` is given.

```bash
php artisan xenforo:import-env [--force]
```

| Option | Required | Description |
|---|---|---|
| `--force` | No | Replace settings that are already saved |

---

### `plugins:list` / `plugins:enable {slug}` / `plugins:disable {slug}` / `plugins:uninstall {slug} [--force]`

List the plugins found in `tracker-app/plugins/` and `storage/app/plugins`, or turn one on or off for the whole site. Enabling runs the plugin's migrations. Disabling keeps its data and settings. Uninstalling disables it, rolls back its migrations and deletes its settings, after a confirmation prompt (`--force` skips it). See [PLUGINS.md](PLUGINS.md).

```bash
php artisan plugins:list
php artisan plugins:enable discord
php artisan plugins:disable discord
```

### `plugins:reconcile`

Run by `deploy.sh` after Composer and migrations. Runs plugin migrations for plugins whose files changed, turns on plugins that were waiting for their packages, and records each plugin's installed version, status and last error for the Plugins page. A plugin whose files are gone is marked missing; its settings and data are kept. Running it again changes nothing ("Every plugin is as it was."). It never fails, so it never stops a deploy. See [DEPLOY.md](DEPLOY.md#plugin-packages-on-deploy).

```bash
php artisan plugins:reconcile
```

### `plugins:packages [--write]` / `plugins:unmet`

`plugins:packages` lists the Composer packages the enabled plugins need; `--write` also saves them for the deploy. `plugins:unmet` lists enabled plugins that can't run right now (files missing or requirements not met). `deploy.sh` runs it before and after installing packages and stops if a plugin that could run before can't any more.

### `make:plugin {name} [--namespace=] [--composer]`

Creates a starter plugin in `tracker-app/plugins/<slug>` from the templates in `tracker-app/stubs/plugin`. `--composer` also writes a `composer.json` for the Composer packages the plugin needs.

```bash
php artisan make:plugin "Event Weather"
```
