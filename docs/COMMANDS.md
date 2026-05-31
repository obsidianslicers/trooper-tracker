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

### `tracker:generate-factories`

Generates Eloquent model factories for all models in the models directory that don't already have one. Useful during development when new models are added.

```bash
php artisan tracker:generate-factories
```

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

### `tracker:simulate-shift-complete`

Creates a test shift-complete scenario for a trooper and outputs confirmation URLs for both "attended" and "unable to attend" responses. Used to test the post-shift update flow without waiting for a real shift to end.

```bash
php artisan tracker:simulate-shift-complete {trooper_id} [--expired] [--dual-costume]
```

| Argument/Option | Required | Description |
|---|---|---|
| `trooper_id` | Yes | ID of the trooper to generate the scenario for |
| `--expired` | No | Sets the shift as ended 45 days ago (outside the 30-day update window) |
| `--dual-costume` | No | Sets up a dual-club costume scenario to trigger the club-selection flow |

**Examples:**
```bash
php artisan tracker:simulate-shift-complete 42
php artisan tracker:simulate-shift-complete 42 --expired
php artisan tracker:simulate-shift-complete 42 --dual-costume
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
