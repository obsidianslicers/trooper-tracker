# Sentry plugin

Sends errors from the server and from members' browsers to [Sentry](https://sentry.io), so they
can be fixed before anyone reports them. It is optional: core's exception emails
(`TRACKER_EXCEPTION_EMAIL_ENABLED`) work without it.

The plugin is off until an administrator enables it (**Command Staff > Plugins**, or
`php artisan plugins:enable sentry`). See [docs/PLUGINS.md](../../../docs/PLUGINS.md) for how
plugins are managed.

## Configuration

You need a Sentry account and a project (or two: one for the server, one for browsers). Copy each
project's DSN into the settings.

| Setting | Type | Fallback | Purpose |
|---------|------|----------|---------|
| **Server DSN** (`dsn`) | url | `SENTRY_DSN` | Server errors go to this project. Without it, server errors aren't sent. |
| **Browser DSN** (`browser_dsn`) | url | `SENTRY_BROWSER_DSN` | Browser errors go to this project. It is visible in every page. Without it, browser errors aren't sent. |
| **Environment** (`environment`) | text | `SENTRY_ENVIRONMENT`, then `APP_ENV` | Shown on each error, such as `production` or `staging`. |
| **Send browser errors through this site** (`tunnel`) | boolean | on | Browsers post to `/webhooks/sentry` instead of sentry.io, which ad blockers often block. |

The plugin needs the `sentry/sentry` Composer package (the plain PHP SDK, not
`sentry/sentry-laravel`), declared in its own `composer.json`. When a site enables the plugin,
the next deploy installs the package and turns the plugin on.

## What it adds

- **Server errors.** A listener on `MessageLogged` sends every exception logged at `error` level
  or above. Core's exception handler logs each unhandled 5xx this way, so this covers web
  requests, queued jobs, console commands, plugin failures and anything passed to `report()`.
  Warnings and messages logged without an exception are not sent.
- **Browser errors.** With a Browser DSN, every page loads Sentry's browser SDK (version
  `11.10.0`, pinned with an integrity hash) through the `page.head` slot. It reports uncaught
  errors and unhandled promise rejections.
- **Tunnel webhook.** `POST /webhooks/sentry` forwards a browser's error envelope only when it is
  addressed to the Browser DSN, and only to that DSN's own Sentry host. It returns 404 when the
  tunnel is off or there is no Browser DSN, refuses bodies over 1 MB, and allows 60 requests a
  minute per IP address.
- **System Check row.** "Sentry server DSN" passes when a server DSN is set.

No pages, database tables, scheduled tasks or console commands.

## What is sent

A trooper's id, never their name, email, IP address, cookies or form input. The server SDK runs
without Sentry's default integrations (Laravel already handles PHP errors), never sends request
bodies, and drops the function arguments PHP keeps on stack frames, since they can hold member
data.

## Limitations

- Only exceptions are sent; there is no performance tracing or session replay.
- The browser SDK is loaded from Sentry's CDN (`browser.sentry-cdn.com`), so a blocker that
  blocks that host still hides browser errors.

## Tests

From `tracker-app`:

```bash
php artisan test plugins/sentry/tests
```

The tests are skipped when `sentry/sentry` is not installed.
