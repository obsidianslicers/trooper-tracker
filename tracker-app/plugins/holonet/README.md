# HoloNet plugin

Garrisons run their own trackers. The HoloNet plugin lets a trooper link their account on another
garrison's tracker, so their service record here also shows the troops they did there. There is
no central server: every tracker is equal, and each link is made by the trooper. Enable it if
your troopers also troop with garrisons that run their own TroopTracker.

The plugin is off until an administrator enables it (**Command Staff > Plugins**, or
`php artisan plugins:enable holonet`). Both trackers in a link need it enabled. See
[docs/PLUGINS.md](../../../docs/PLUGINS.md#the-holonet-plugin) for the full description of the
link flow.

## Configuration

`APP_URL` must be the tracker's public HTTPS address: that is how other trackers know it.

| Setting | Type | Default | Purpose |
|---------|------|---------|---------|
| **Tracker name** (`tracker_name`) | text | the site name | How this tracker is named to troopers on other trackers. |
| **Let troopers share their history with other trackers** (`share_history`) | boolean | on | When off, troopers can still link other trackers here, but other trackers can't read history from this one. |
| **Tracker list URL** (`directory_url`) | url | `HOLONET_DIRECTORY_URL`, then `trackers.json` on GitHub | Where the list of supported trackers is read from. |

**Joining the list.** Links only work between trackers listed in
[`trackers.json`](trackers.json) (name and HTTPS address). A garrison joins with a pull request
adding its tracker. Each tracker reads the list from GitHub (cached for a day) or from the
**Tracker list URL** setting, plus the copy bundled with the plugin.

Other `.env` values, all in [`config/config.php`](config/config.php): `HOLONET_TIMEOUT` (seconds to
wait for another tracker, default 10) and the `HOLONET_LIMIT_*`, `HOLONET_REFRESH_COOLDOWN` and
`HOLONET_NIGHTLY_JITTER` rate limits below.

## What it adds

**For troopers**

- **Profile > HoloNet** (`/plugins/holonet`, also a button on their own service record) lists
  their links. To add one they pick a tracker, sign in there and press Allow; they can refresh or
  unlink each one. Under "Trackers that can see your history here" they can remove another
  tracker's access.
- **Service record.** "HoloNet", "All-Time Troops" and "All-Time Hours" in the Service Summary,
  and a "Troops from the HoloNet" table under History. A troop that overlaps one recorded here,
  or one from another linked tracker, counts once.

**Routes**

| Route | Who | Purpose |
|-------|-----|---------|
| `GET /plugins/holonet` | signed-in active troopers | Linked trackers page |
| `POST /plugins/holonet/links`, `GET /plugins/holonet/callback` | troopers | Start and finish a link |
| `POST /plugins/holonet/links/{link}/refresh`, `DELETE /plugins/holonet/links/{link}` | troopers | Refresh, unlink |
| `GET`/`POST /plugins/holonet/authorize` | troopers | The Allow page another tracker sends them to |
| `DELETE /plugins/holonet/grants/{grant}` | troopers | Remove another tracker's access |
| `GET /plugins/holonet/instance` | anyone | Tells another tracker whether HoloNet is on and shares history |
| `POST /webhooks/holonet/token` | other trackers | Swap a one-time code (with the PKCE verifier) for an access key |
| `POST /webhooks/holonet/history` | other trackers | Read one trooper's history with a Bearer access key |
| `POST /webhooks/holonet/revoke` | other trackers | Forget an access key on unlink |

The link follows the OAuth authorization code flow with PKCE. Codes last 5 minutes and are stored
hashed; the sharing side keeps only a hash of each access key.

**Scheduled task and console command**

- `php artisan holonet:refresh-links [--spread]` copies every active link's history again. It is
  scheduled daily at 04:00 with `--spread`, which waits a random time up to an hour first.
  The copy is replaced whole each time. If the other tracker can't be reached the old copy
  stays; if it refuses the key, the copy is removed.

**System Check row.** "Linked trackers: this tracker is on the list" warns while this tracker is
missing from the list.

**Tables**

| Table | Holds |
|-------|-------|
| `tt_trooper_holonet_links` | A trooper's links to other trackers, with the access key |
| `tt_trooper_holonet_troops` | The troops copied from each link |
| `tt_trooper_holonet_grants` | Other trackers allowed to read a trooper's history here (key hashes) |
| `tt_trooper_holonet_codes` | One-time link codes (hashed) with their PKCE challenges |

## What is shared

Only attended shifts of closed events recorded on the sharing tracker: event name, type and
venue, shift times, costume, the clubs credited, and whether the trooper was a handler. Never
emails, phones, legal names, contacts or other troopers. A tracker only shares troops it recorded
itself, never ones it copied from another tracker.

## Rate limits

A tracker over a limit gets HTTP 429 with `Retry-After`.

| What | Default |
|------|---------|
| Code exchanges | 30 a minute per IP address |
| History reads | 60 a minute per access key, 300 a minute per IP address |
| Unlink notices | 30 a minute per IP address |
| HoloNet checks (`/instance`) | 60 a minute per IP address |
| A trooper's Refresh button | once every 5 minutes per link |
| A trooper starting a link, or pressing Allow | 10 an hour |

The nightly refresh runs one request at a time, tracker by tracker. When a tracker answers 429,
no request goes to it until its `Retry-After` has passed (at least a minute, at most a day).

## Limitations

- Linked troops never count toward this tracker's reports, leaderboards, awards, achievements or
  club credit; they only appear on the service record.
- Troopers under 18 (or with a guardian) can't link or allow a link.
- Only trackers on the list can link, each at the address in its `APP_URL`. Plain `http://`
  addresses are only accepted when `APP_ENV` is `local` or `testing`.
- At most 10,000 troops are accepted from one tracker in one refresh.

To try it locally, run two copies on different ports with separate databases and point both at a
shared tracker list; see [docs/PLUGINS.md](../../../docs/PLUGINS.md#the-holonet-plugin).

## Tests

From `tracker-app`:

```bash
php artisan test plugins/holonet/tests
```
