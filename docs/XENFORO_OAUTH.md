# XenForo Integration

Troop Tracker supports a full XenForo-backed workflow, not just login.

When XenForo integration is fully configured, Troop Tracker can:

- authenticate users with XenForo OAuth
- require XenForo-linked accounts for access
- create event forum threads automatically
- update event forum posts as roster details change
- move event threads to an archive forum when events close
- read forum replies and display them on event pages
- synchronize Troop Tracker data back to XenForo user profiles and groups
- calculate support totals from XenForo user upgrades
- display XenForo user-group banner HTML on trooper service-record pages

This guide covers what the integration does, why the required add-ons exist, and how to set everything up.

## Overview

Troop Tracker uses XenForo in two separate ways:

### 1. XenForo OAuth

This is used for login and account linking.

- user signs in with XenForo
- Troop Tracker stores the XenForo `user_id` in `tt_oauth_logins.provider_id`
- that linked XenForo ID is reused for API-driven features like event posting, banner lookups, and user synchronization

### 2. XenForo API Integration

This is used for forum automation and profile data.

- create threads
- update posts
- read users
- update users
- read thread replies
- read custom add-on endpoints for support totals and user-group banners

Troop Tracker treats these as related but distinct pieces. OAuth can be configured without the broader API integration, but the full XenForo feature set requires both.

## Required Add-on

One consolidated XenForo add-on is required when you want the full XenForo integration enabled.

### Troop Tracker XenForo Addon

Repository:

- https://github.com/obsidianslicers/troop-tracker-xenforo-addons

Addon ID: `ObsidianSlicers/TroopTracker` — requires XenForo 2.2.0+. Install the single zip through XenForo Admin CP.

This add-on bundles all integration features:

#### Upgrade Stats API

What it does:

- adds a XenForo API endpoint for upgrade and payment-related data
- exposes:
    - `userUpgradeActive`
    - `userUpgradeExpired`
    - `userUpgrades`
    - `combinedResults`
    - `paymentLog`

Endpoint:

- `GET /index.php?api/upgrade-stats`

Custom API scope:

- `upgrades:read`

Why Troop Tracker needs it:

- Troop Tracker uses this endpoint to calculate support totals from XenForo upgrades instead of relying only on local donation records.
- This allows the support widget to reflect recurring supporter revenue already managed by XenForo.

Benefit:

- one source of truth for supporter upgrades
- less manual duplication of support data
- support totals match the forum’s active upgrade state

#### User Groups API

What it does:

- adds a XenForo API endpoint that returns a user’s primary and secondary groups
- includes each group’s:
    - `groupID`
    - `title`
    - `bannerText`
    - `order`
    - `isPrimary`

Endpoint:

- `GET /index.php?api/user-groups&user_id={xenforoUserId}`

Custom API scope:

- `usergroups:read`

Why Troop Tracker needs it:

- Troop Tracker uses this endpoint to display XenForo banner HTML on the trooper service-record page.
- This keeps rank and status presentation aligned with the forum rather than reimplementing banner logic inside Troop Tracker.

Benefit:

- forum rank and banner styling stays authoritative in XenForo
- Troop Tracker profile pages automatically reflect XenForo group presentation
- no duplicate banner rules inside Troop Tracker

#### View Attachment (mobile)

What it does:

- provides a XenForo API endpoint that allows the mobile app to safely render attachments in troop chat and event-related contexts

#### Ignore Users (mobile)

What it does:

- exposes API endpoints for blocking/unblocking and reporting users/posts from the Troop Tracker mobile app, backed by XenForo’s ignore and report systems

## XenForo Features in Troop Tracker

### XenForo OAuth Login

Troop Tracker includes a custom Socialite provider for XenForo.

What it provides:

- XenForo OAuth login
- account linking to a Troop Tracker user
- XenForo avatar retrieval
- persistent mapping from Troop Tracker user to XenForo `user_id`

Relevant config:

- `XENFORO_CLIENT_ID`
- `XENFORO_CLIENT_SECRET`
- `XENFORO_REDIRECT_URI`
- `XENFORO_BASE_URL`
- optional endpoint path overrides in `config/services.php`

### XenForo-Required Accounts

If `TRACKER_REQUIRE_XENFORO=true`, Troop Tracker can enforce XenForo-based access.

What happens when enabled:

- email/password login is disabled
- non-XenForo OAuth providers are blocked
- authenticated users without a linked XenForo account are redirected to the XenForo linking flow

Benefit:

- every active Troop Tracker user is tied to a forum identity
- all downstream XenForo features can safely resolve a XenForo `user_id`

### Event Thread Creation

When an event is created and the organization has a related XenForo forum configured, Troop Tracker can create a XenForo thread automatically.

What gets posted:

- event title
- event details rendered as BBCode
- roster summary

Benefit:

- event announcements appear in the forum automatically
- moderators do not need to duplicate event posts manually

### Event Thread Updates

Troop Tracker can update the first post of the XenForo thread as event data changes.

What is updated:

- roster summary
- thread body generated from the current event state

Benefit:

- forum thread stays current without manual edits

### Event Thread Archiving

When events close, Troop Tracker can move the XenForo thread into an archive forum.

Benefit:

- active forums stay cleaner
- historical event threads remain organized automatically

### Forum Reply Display

Troop Tracker can read recent XenForo thread replies and display them on event pages.

Benefit:

- event discussion remains visible from inside Troop Tracker
- users do not need to switch back and forth as often

### XenForo User Synchronization

Troop Tracker can push user metadata and secondary-group memberships back to XenForo.

What is synchronized:

- `custom_fields[trackerid]`
- `custom_fields[fullname]`
- `custom_fields[organizations]`
- `secondary_group_ids`

How group sync works:

- Troop Tracker resolves the linked XenForo `user_id`
- Troop Tracker reads the current XenForo user
- it preserves non-Troop-Tracker-managed secondary groups
- it adds or removes organization-related XenForo groups based on Troop Tracker memberships

Benefit:

- forum permissions and profile metadata can reflect Troop Tracker membership data
- organization status can be maintained centrally in Troop Tracker

Commands:

- `php artisan tracker:synchronize-xenforo-user {trooper}`
- `php artisan tracker:synchronize-xenforo-users`

### Support Total Integration

Troop Tracker can calculate support totals using the XenForo upgrade stats endpoint.

Benefit:

- supporter totals match the forum upgrade system
- local UI reflects live XenForo subscription state

### XenForo User-Group Banners on Profiles

Troop Tracker can display XenForo `bannerText` on trooper service-record pages.

How it works:

- Troop Tracker resolves the trooper’s linked XenForo ID from `tt_oauth_logins`
- it calls the user-groups add-on endpoint
- it filters out empty banners
- it renders the returned HTML below the trooper name and title

Benefit:

- forum badge presentation carries over to Troop Tracker
- no duplicate rank/banner system is needed in the app

## Benefits of the XenForo Integration

- one identity across the forum and Troop Tracker
- less duplicate data entry for moderators and users
- automatic event thread publishing and maintenance
- unified support reporting from XenForo upgrades
- synchronized organization-driven XenForo permissions
- richer profile presentation using XenForo banners

In practice, XenForo becomes the identity and community layer, while Troop Tracker becomes the operations and events layer.

## Setup Checklist

Complete all steps below to enable the full integration.

### 1. Install the XenForo add-on

Download and install the single zip from:

- https://github.com/obsidianslicers/troop-tracker-xenforo-addons

Install through XenForo Admin CP. The package (Addon ID: `ObsidianSlicers/TroopTracker`) includes all required endpoints.

### 2. Create a XenForo OAuth client

In XenForo Admin CP:

1. Go to `Setup > OAuth2 clients`
2. Create a new OAuth client
3. Set the redirect URI to your Troop Tracker callback URL

Example:

- `https://your-troop-tracker.example.com/oauth/xenforo/callback`

Record these values:

- client ID
- client secret

### 3. Create or update a XenForo API key

Create an API key that Troop Tracker can use for forum automation and the custom add-on endpoints.

The key must be able to access:

- XenForo users endpoints
- XenForo threads endpoints
- XenForo posts endpoints
- custom scope `upgrades:read`
- custom scope `usergroups:read`

If your XenForo setup limits API scopes aggressively, confirm the API key can read users and create/update threads and posts in addition to the two custom scopes.

### 4. Configure Troop Tracker environment variables

Set these in `.env`:

```env
XENFORO_CLIENT_ID=your-xenforo-oauth-client-id
XENFORO_CLIENT_SECRET=your-xenforo-oauth-client-secret
XENFORO_REDIRECT_URI=https://your-troop-tracker.example.com/oauth/xenforo/callback
XENFORO_BASE_URL=https://your-forum.example.com/boards
XENFORO_NAME="Your Forum Name"

XENFORO_API_KEY=your-xenforo-api-key
XENFORO_API_USER=1

TRACKER_REQUIRE_XENFORO=true
```

Notes:

- `XENFORO_BASE_URL` should be the forum base URL without a trailing slash
- `XENFORO_API_USER` is the acting XenForo API user ID, often `1` in small installs
- `TRACKER_REQUIRE_XENFORO=true` is recommended if XenForo is your required identity provider

### 5. Optional: confirm endpoint path overrides

Defaults in `config/services.php` assume XenForo 2.3 style endpoints:

- `XENFORO_AUTHORIZE_PATH=/index.php?oauth2/authorize`
- `XENFORO_TOKEN_PATH=/index.php?api/oauth2/token`
- `XENFORO_ME_PATH=/api/me`

If your XenForo install differs, override those values in `.env`.

### 6. Clear Laravel config cache

After changing `.env` values:

```bash
php artisan config:clear
php artisan cache:clear
```

If you cache config in production, rebuild it afterward.

### 7. Link users through XenForo OAuth

Troop Tracker features such as banner display, event posting as a user, and user synchronization depend on a linked XenForo account.

Troop Tracker resolves the XenForo user by reading:

- `tt_oauth_logins.provider = xenforo`
- `tt_oauth_logins.provider_id = XenForo user_id`

If a trooper is not linked, Troop Tracker cannot resolve the XenForo `user_id` for that person.

### 8. Configure organization forum mappings

For event thread automation, configure organization-level forum IDs in Troop Tracker.

You will need:

- active event forum node ID
- archive forum node ID

These are used to:

- create event threads in the correct forum
- move threads to the archive when events close

### 9. Configure organization XenForo group mappings

If you want organization-based group synchronization, populate the XenForo group ID fields on organizations.

Troop Tracker supports mapping:

- active group ID
- reserve group ID
- retired group ID

These mappings are used by the XenForo user sync service.

### 10. Run synchronization if needed

To push Troop Tracker data to XenForo after setup:

```bash
php artisan tracker:synchronize-xenforo-users
```

For a single user:

```bash
php artisan tracker:synchronize-xenforo-user 644
```

## Validation Steps

After setup, verify each layer:

### OAuth

- log in through XenForo
- confirm an `oauth_logins` record is created with provider `xenforo`

### API integration

- verify Troop Tracker can read a XenForo user
- verify event thread creation works for an organization with a related forum

### Upgrade stats add-on

- confirm `GET /index.php?api/upgrade-stats` returns JSON
- confirm support totals in Troop Tracker match XenForo upgrade data

### User-groups add-on

- confirm `GET /index.php?api/user-groups&user_id={id}` returns JSON
- confirm service-record pages show XenForo banners for linked users

### User synchronization

- run the sync command
- confirm XenForo custom fields and secondary groups update as expected

## Troubleshooting

### Login works but banners or forum features do not

Possible causes:

- `XENFORO_API_KEY` is missing or invalid
- add-ons are not installed in XenForo
- linked user does not have a XenForo OAuth mapping in `tt_oauth_logins`

### Banners do not appear on the service-record page

Check:

- XenForo integration is configured
- the trooper has `provider = xenforo` in `tt_oauth_logins`
- `provider_id` contains the correct XenForo `user_id`
- the user-groups endpoint returns non-empty `bannerText`

### Support total does not use XenForo data

Check:

- the upgrade-stats add-on is installed
- the API key includes `upgrades:read`
- the endpoint returns upgrade data for the current month

### Event threads are not created or updated

Check:

- organization forum IDs are configured
- API key can access thread and post endpoints
- the event creator has a linked XenForo account if thread creation should be performed as that user

### User sync does not apply expected groups

Check:

- organization XenForo group IDs are populated
- trooper memberships are correct in Troop Tracker
- the linked XenForo account exists

## References

- [Authentication Guide](AUTHENTICATION.md)
- [Environment Variables](ENVIRONMENT_VARIABLES.md)
- https://github.com/obsidianslicers/troop-tracker-xenforo-addons
