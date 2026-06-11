# Artisan Commands

All custom commands use the `tracker:` or `xenforo:` namespace and are run via:

```bash
php artisan <command> [arguments] [options]
```

---

## Scheduled Commands

These commands run automatically via the scheduler defined in `routes/console.php`. All times use the timezone configured in `tracker.calendar.timezone`.

| Command | Schedule |
|---|---|
| `tracker:synchronize-xenforo-users` | Every hour |
| `tracker:close-event-shifts` | Every hour |
| `tracker:close-events` | Daily at 1:00 AM |
| `tracker:calculate-trooper-achievements` | Daily at 2:00 AM |
| `tracker:expire-visitor-access` | Daily at 12:30 AM |
| `tracker:send-daily-event-notifications` | Daily at 8:00 AM |
| `tracker:process-account-deletions` | Daily at 3:30 AM |

---

## Command Reference

### `tracker:calculate-trooper-achievements`

Recalculates trooper ranks for all troopers based on their event history.

Dispatches `RecalculateTrooperRankCommand` through the message bus. Reports execution time when complete.

```bash
php artisan tracker:calculate-trooper-achievements
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

Notifies visitor troopers whose 6-month access window has elapsed. Dispatches `NotifyExpiredVisitorCommand` for each expired visitor. Does not modify membership status — renewal requires a separate request.

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

### `tracker:process-account-deletions`

Permanently anonymizes and soft-deletes trooper accounts that have been pending deletion for 30 or more days. Picks up any account where `deletion_requested_at` is set and the 30-day grace period has elapsed.

Run automatically by the scheduler. Can also be run manually to force-process any overdue accounts.

```bash
php artisan tracker:process-account-deletions
```

---

### `tracker:generate-factories`

Generates Eloquent model factories for all models in the models directory that don't already have one. Useful during development when new models are added.

```bash
php artisan tracker:generate-factories
```

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

Sends consolidated daily email digests to troopers who have upcoming events. Queries troopers eligible for notification and dispatches `SendEventDailyNotificationCommand` for each.

```bash
php artisan tracker:send-daily-event-notifications
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

Synchronizes all organizations that have a configured service class. Instantiates each service class via the container, runs the sync, and reports timing and success/failure per organization.

```bash
php artisan tracker:synchronize-organizations
```

---

### `tracker:synchronize-xenforo-user`

Syncs a single trooper's data to XenForo.

```bash
php artisan tracker:synchronize-xenforo-user {trooper_id}
```

| Argument | Required | Description |
|---|---|---|
| `trooper_id` | Yes | ID of the trooper to sync |

**Example:**
```bash
php artisan tracker:synchronize-xenforo-user 42
```

---

### `tracker:synchronize-xenforo-users`

Syncs all troopers to XenForo, processed in chunks to avoid memory issues.

```bash
php artisan tracker:synchronize-xenforo-users [--chunk=<size>]
```

| Option | Default | Description |
|---|---|---|
| `--chunk` | `100` | Number of troopers to process per chunk (minimum: 1) |

**Example:**
```bash
php artisan tracker:synchronize-xenforo-users --chunk=50
```

---

### `xenforo:test-thread`

Creates a test thread in a XenForo forum node and outputs the raw JSON response. Useful for verifying XenForo API connectivity and thread creation.

```bash
php artisan xenforo:test-thread {node_id} {title} {message} [--user_id=] [--prefix_id=] [--extra=*]
```

| Argument/Option | Required | Description |
|---|---|---|
| `node_id` | Yes | XenForo forum/node ID to post in |
| `title` | Yes | Thread title |
| `message` | Yes | Thread body |
| `--user_id` | No | XenForo user ID for the thread creator |
| `--prefix_id` | No | XenForo thread prefix ID |
| `--extra` | No | Extra fields as `key:value` pairs (repeatable) |

**Example:**
```bash
php artisan xenforo:test-thread 5 "Test Thread" "Hello world" --user_id=1 --prefix_id=3
php artisan xenforo:test-thread 5 "Test" "Body" --extra=field1:val1 --extra=field2:val2
```
