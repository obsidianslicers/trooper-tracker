# Google Sheets plugin

Lets the club synchronizers and member lookups read a club's roster from a Google Sheet. Some
clubs (for example Rebel Legion, Droid Builders and Saber Guild) publish their rosters as Google
Sheets; core's synchronizers ask for a "sheet source" to read them, and this plugin is that
source. Enable it if any of your clubs sync from a Google Sheet. Without it, those syncs and
lookups read nothing.

The plugin is off until an administrator enables it (**Command Staff > Plugins**, or
`php artisan plugins:enable google-sheets`). See [docs/PLUGINS.md](../../../docs/PLUGINS.md) for
how plugins are managed.

## Configuration

| Setting | Type | Default | Purpose |
|---------|------|---------|---------|
| **Service account credentials file** (`credentials_path`) | text | `google-credentials.json` | Path to the Google service account JSON key, relative to `tracker-app` unless it is absolute. |

You need:

1. A Google Cloud service account with the Google Sheets API enabled, and its JSON key file on
   the server at the path above.
2. Each club's sheet shared with the service account's email (read access is enough; the plugin
   only asks for the `spreadsheets.readonly` scope).
3. The sheet's ID saved in **Google Sync Sheet ID** on the club's organization edit page.

The plugin needs the `google/apiclient` Composer package, declared in its own `composer.json`.
The app does not require it: when a site enables the plugin, the next deploy installs the package
and turns the plugin on.

## What it adds

- **Sheet source.** `GoogleSheetSource` reads a named tab of the club's saved sheet. Transient
  Google errors (HTTP 429/500/503) are retried 3 times with backoff; requests time out after
  30 seconds. When Google can't be reached or the credentials file is missing, the read fails
  with `SheetUnavailableException` and a log warning.
- **Organization form field.** **Google Sync Sheet ID** (max 128 characters), shown only on
  top-level club organizations (`admin.organizations.update.fields` slot). Saving it empty
  removes it.
- **System Check row.** "Google Sheets credentials" passes when the credentials file is readable.
- **Table.** `tt_organization_google_sheets` (one sheet per organization). Its migration copies
  any values left in the old core column `tt_organizations.sync_sheet_id`.
- **Seeder.** `ClubSheetsSeeder` stores the known sheet IDs for Rebel Legion, Droid Builders and
  Saber Guild:

  ```bash
  php artisan db:seed --class="TroopTracker\Plugins\GoogleSheets\Database\Seeders\ClubSheetsSeeder"
  ```

No routes, pages, scheduled tasks or console commands of its own; the synchronizers that read
the sheets are core code.

## Limitations

- Read-only: nothing is ever written back to a sheet.
- One sheet per organization.
- Google Maps geocoding of event addresses is not part of this plugin; it stays in core
  (`GOOGLE_MAPS_API_KEY`).

## Tests

From `tracker-app`:

```bash
php artisan test plugins/google-sheets/tests
```

The tests are skipped when `google/apiclient` is not installed.
