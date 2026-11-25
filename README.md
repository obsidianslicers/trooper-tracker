# Troop Tracker

[![Laravel Style](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/pint.yml/badge.svg)](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/pint.yml) [![Laravel Tests](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/tests.yml/badge.svg)](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/tests.yml)

**Troop Tracker** is the Empire's official operations dashboard, engineered to impose order upon trooper assignments, moderation workflows, and hierarchical communications across organizations, regions, and units. Forged with Laravel, Inertia, Svelte, and Bootstrap, it delivers the precision, discipline, and ruthless efficiency expected of any system operating under Imperial authority. Legacy Blade and Alpine screens remain during the frontend migration. Each club bends it to its own doctrine through [plugins](#customizing-with-plugins): forum logins, Discord announcements, rosters read from Google Sheets and more, without touching core code.

Currently Used By:
* Florida Star Wars Clubs
  * [501st Legion, Florida Garrison](https://www.facebook.com/FloridaGarrison501st/)
  * [Mandaloria Mercs, House Buurenaar Verda](https://www.facebook.com/BuurenaarVerda/)
  * [Rebel Legion, Ra Kura Base](https://www.facebook.com/rakurabase/)
  * [Saber Guild, Takodana Temple](https://www.facebook.com/takodanatemple/)
  * [Saber Guild, Dagobah Temple](https://www.facebook.com/dagobahtemple/)
  * [Dark Empire, Spire of the Storm](https://www.facebook.com/SpireoftheStormTDE/)
  * [Galactic Academy, Dagobah School](https://www.facebook.com/DagobahGalacticAcademyFlorida/)

---

## TL;DR for Collaborators

- Troop Tracker is a Laravel 12 application platform for Star Wars costuming clubs, focused on trooper profiles, event/troop coordination, organization hierarchy, notices, and approvals.
- Backend is a Laravel 12 application built around organized domain features, role-based access control, and structured event/member workflows.
- Frontend migration has moved interactive screens from Blade/HTMX/Alpine to Inertia + Svelte 5; new interactive work should follow the Svelte architecture.
- Backend domain operations use single-file Hyperdrive Messages in `app/Messages/` (Commands, Queries, PageData). See [Hyperdrive Message Architecture](tracker-app/packages/hyperdrive/README.md) for details.
- Authentication supports email/password and Google, plus login providers added by plugins (XenForo); all new accounts go through pending/admin approval.
- Optional integrations (Discord, Google Sheets, HoloNet, Sentry, XenForo) are plugins under `tracker-app/plugins/`; see [Plugins](docs/PLUGINS.md).
- Local development basics: install with Composer/NPM, migrate and seed, then run `composer dev` from `tracker-app/`.
- Contribution gates before PR: tests (`php artisan test --parallel`, `npm test`, and `npm run test:e2e` for page changes), formatting (`composer pint:format`) and translations (`php artisan tracker:report-translations --strict`).
- Best docs to read first: [CLAUDE.md](CLAUDE.md) (the team's conventions, for people and AI assistants alike), [Architecture](docs/ARCHITECTURE.md), [Coding Conventions](docs/CODING_CONVENTIONS.md), [Project Structure](docs/PROJECT_STRUCTURE.md), and [Database](docs/DATABASE.md).
- Collaboration workflow: branch from `main`, open a PR whose title follows the commit format `<type>: <short all lower case message>` (`feature`, `fix`, `style`, `refactor`, `perf`, `test`, `docs`, `chore`). PRs are squash-merged, so the title becomes the commit on `main`. See [Contributing](CONTRIBUTING.md).

---

## Status Report: Development Proceeds at the Empire's Pace

This project remains under active development, which is to say it currently exists in a state of sanctioned chaos. Features may appear, disappear, or behave unpredictably without prior notice, as is their prerogative during this phase of imperial construction. Should you encounter bugs, inconsistencies, or architectural decisions that defy mortal comprehension, rest assured they are merely temporary artifacts of progress. Proceed with caution, submit issues with appropriate deference, and remember: stability will arrive when it is commanded to arrive, and not a moment sooner.

Progress continues at a pace deemed acceptable by the Empire. New features, refinements, and the occasional miracle will be deployed as they reach a state worthy of consumption. Garrison Liasons are encouraged to return in approximately one month to witness the next phase of sanctioned advancement. Until then, patience is not only advised — it is expected.

**Update February 18th**: Despite rumors to the contrary, progress has not stalled. In fact, the project currently stands at an estimated 85% operational readiness, supported by a test suite now exceeding 2,000 trials of loyalty. Minor rebellions within the codebase are being suppressed with appropriate vigor. Observers may take this as a sign that stability is approaching, though only the Empire may determine when "approaching" becomes "arrived." Sensible personnel are advised to check back in roughly one month for the next sanctioned update, assuming the system has not evolved beyond the need for such courtesies.

**Update March 8th**: Troop Tracker edges ever closer to UAT, with performance‑rebellious tests being rewritten, optimized, and reminded of their place in the hierarchy; work continues on the configurable XenForo integration, ensuring it can ultimately operate either as a seamless Imperial fusion or a proudly isolationist standalone deployment; and the visual command interfaces have been refreshed with updated Stormtrooper, Bounty Hunter, Rebel, Clone, and Sith themes, each calibrated for maximum intimidation, usability, or in the case of Rebels, remedial hand‑holding — overall, stability is rising, features are aligning, and the system marches toward UAT with the slow, inevitable certainty of an Imperial Star Destroyer entering orbit.

**Update April 13th**: The waiting is over. Troop Tracker has officially entered UAT, and the Empire has invited brave volunteers to click every button, break every edge case, and report their findings before Lord QA starts force-choking random merge requests. Core systems are stable, workflows are operational, and most remaining issues now live in the category of "annoying but survivable." If you spot defects, file them with precision and without panic. If you do not spot defects, click harder. UAT is live, and yes, this is the part where we pretend everything was always under control. 

**Update May 29th Troop Tracker Is Live — Kneel or Be Logged** 

The day has arrived. Troop Tracker has officially gone LIVE, meaning the restraining bolts have been removed, the excuses have expired, and every button you click now counts. The Empire assures you the system is "ready," which is the closest thing to a guarantee you're ever going to get. Welcome to production. Try not to break anything important.

Core systems are deployed, workflows are operational, and all known defects have been either resolved, documented, or strategically ignored for future generations to discover. From this point forward, any issues you encounter are no longer "feedback" — they are real problems, with real consequences, and real opportunities for the Obsidian Slicers to sigh loudly in your direction.

Use the system boldly. Report issues responsibly. Pretend everything is stable.
Troop Tracker is live, the Empire ascends, we will now act like this was the plan all along.

**Update July 28th**: The Imperial Desk Duty Clerks submit the following condensed summary for your reluctant enlightenment: over the past cycle, the Obsidian Slicers have been busily patching your self‑inflicted mishaps — achievements now announce themselves properly, organizations receive their  recognition, guests have been prevented from detonating the system, and forum quotes function for those who insist on narrating their lives. Milestone emails now include your legal name so we may address reprimands accurately, events close when they're actually finished, rosters sync without collapsing, costume selections behave, handlers and guests are counted, and trooper requests can be dismissed until you inevitably bother us again. Appeals exist for those who enjoy paperwork, milestones notify as intended, costume credit goes to the correct club, right‑clicking events is now officially tolerated, approvals verify your allegiance, invalid accounts are cataloged, moderators have been reminded they do in fact possess authority, charity math has been corrected, OAuth has stopped screaming, timezones have been subdued, and XenForo has been dragged into compliance. In short: the system works, mostly, and the Empire politely requests fewer panicked messages from personnel who clearly did not read this report.

**Update August 22nd**: The Imperial Desk Duty Clerks submit its latest report as the final holdouts of the legacy interface are being marched, one page at a time, toward the brighter and considerably less temperamental future of Svelte. A few troublesome screens have already been migrated to the new front-end framework, where they are expected to behave with improved responsiveness, cleaner structure, and fewer dramatic episodes of unexpected chaos. The remaining pages are being refactored into smaller components and more disciplined state handling, because apparently even the Empire has learned that a dashboard should not be a hostage situation. In short: the migration continues, the old pages are being politely decommissioned, and the system is gradually becoming faster, cleaner, and less likely to explode in a shower of JavaScript indignity.

**Update November 1st**: The Empire welcomes the Midwest Garrison to the Troop Tracker initiative, further extending our administrative reach into sectors previously afflicted by spreadsheets, guesswork, and manual record keeping. Their arrival coincides with a significant increase in development throughput as AI has been drafted into service and assigned directly to both feature development and bug hunting responsibilities. New capabilities are now arriving faster than blaster fire in a cantina dispute, while defects are being located, categorized, and dealt with before they can organize a proper rebellion. Reports indicate productivity is up, response times are down, and the backlog has begun displaying visible signs of panic. The Imperial Clerks remain cautiously optimistic, which in administrative terms is equivalent to outright celebration.

---

## Architecture

Troop Tracker follows the ADR pattern: controllers remain thin orchestrators, domain operations are implemented as Hyperdrive Messages, and Inertia + Svelte provides the target interactive frontend. Blade and Alpine screens remain during the frontend/backend migration. See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) and the [Hyperdrive Message Architecture](tracker-app/packages/hyperdrive/README.md) for details.

Key references:
- [docs/CODING_CONVENTIONS.md](docs/CODING_CONVENTIONS.md) for standards and conventions
- [docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md) for the repo layout
- [docs/DATABASE.md](docs/DATABASE.md) for schema and relationships

---

## Repository Structure

- [tracker-app/](tracker-app/) — Laravel application and runtime code
- [docs/](docs/) — architecture, database, auth, and contributor documentation
- [README.md](README.md), [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md), [CONTRIBUTING.md](CONTRIBUTING.md) — root project documents

---

## Features

### Core Capabilities

*   **Hierarchical Access Control**: Strict Organization → Region → Unit permissions with automatic inheritance and scoped visibility via authorization policies
*   **Trooper Management**: Multi‑organization membership, role‑based permissions (member, handler, visitor, event coordinator, moderator, staff officer, system owner), notification preferences, costume tracking, and achievement badges
*   **Event & Shift Management**: Full event lifecycle (draft/open/closed/cancelled, plus manual selection), multi‑shift scheduling with optional stations, organization-specific invitations and limits, and sign-up tracking (going, tentative, stand-by, pending a costume, then attended or unable to attend)
*   **Interactive Svelte Screens**: Reactive UI for every interactive workflow
*   **Smart Notifications**: Configurable frequency (never/instant/daily), event creation/cancellation emails, daily digest aggregation
*   **Awards & Recognition**: Organization-based awards with frequency controls (once, random, monthly, quarterly, annually), multi-recipient support
*   **Notice System**: Organization-scoped announcements with read tracking and type-based styling (info, success, warning, danger)
*   **Event Photo Gallery**: Photo uploads with trooper tagging, large/thumbnail variants, administrative photo flags
*   **Multiple Languages**: Every page, email and notification can be translated; each trooper picks their language and the site has a default ([docs/LOCALIZATION.md](docs/LOCALIZATION.md))
*   **Customizable with Plugins**: Clubs add their own integrations and behaviour without forking the app; see [Customizing with Plugins](#customizing-with-plugins)

### Developer Experience

*   **Message-Organized Code**: Domain operations grouped by business area under `app/Messages`, with Hyperdrive handling dispatch and hydration
*   **Component-Driven Frontend**: Inertia + Svelte 5 for migrated interactive screens, with Blade retained for static and legacy pages
*   **Incremental Migration**: Remaining Blade/Alpine pages stay operational while their workflows are progressively refactored to the Hyperdrive Message + Svelte target architecture
*   **Auto-Generated Models**: MySQL with Reliese Laravel base model generation
*   **Comprehensive Testing**: PHPUnit feature tests (controllers, form requests, jobs, console commands) and unit tests (messages, policies, rules, services), Vitest for Svelte view models and front-end modules, and Playwright for pages and user flows
*   **Policy-Based Authorization**: Scoped access control for all resources
*   **Audit Trail**: Polymorphic change tracking with trooper stamps

---

## Customizing with Plugins

Troop Tracker is built so a club can fit it to how it operates without changing core code. Plugins live in `tracker-app/plugins/<slug>` (or arrive through Composer, or as a zip uploaded under Command Staff > Plugins), and every plugin stays off until an administrator enables it.

A plugin can:

- **Add ways to log in**, such as a forum account (XenForo)
- **Add to the UI** through page slots and extra form fields
- **React to what happens** through app events and hooks before or after any Hyperdrive message, for example announcing a new event in Discord
- **Sync rosters and data** with outside systems through club synchronizers, sheet sources and webhooks
- **Bring its own settings, pages, routes, migrations, scheduled tasks and Artisan commands**
- **Add notification categories, donation sources, permission requests and System Check rows**

Bundled plugins:

| Plugin | What it adds |
|---|---|
| [Discord](tracker-app/plugins/discord/README.md) | Announces each new event in a Discord channel, mentioning the hosting organization's role |
| [Google Sheets](tracker-app/plugins/google-sheets/README.md) | Lets club synchronizers and member lookups read a club's roster from a Google Sheet |
| [HoloNet](tracker-app/plugins/holonet/README.md) | Links a trooper's accounts on other garrisons' trackers, so their service record shows every troop |
| [Sentry](tracker-app/plugins/sentry/README.md) | Sends server and browser errors to Sentry |
| [XenForo](tracker-app/plugins/xenforo/README.md) | Forum login, user sync, a forum thread per event with its replies, forum notifications, forum groups and forum donations |

Enable, disable or uninstall them under **Command Staff > Plugins** or with `php artisan plugins:enable <slug>`. [docs/PLUGINS.md](docs/PLUGINS.md) covers managing plugins, every hook and slot, and writing your own (`php artisan make:plugin`).

---

## Tech Stack

- **PHP 8.x+** with strict types and scalar type hints
- **Laravel 12.x**, with the app's own email/password login and Socialite for Google and plugin login providers
- **Database**: MySQL (production), SQLite (testing)
- **Frontend target**: Inertia 2 + Svelte 5 + TypeScript + Bootstrap 5.2x
- **Frontend legacy**: Blade templates + Alpine 3.x
- **Backend**: Hyperdrive Messages (Commands, Queries, PageData) in `app/Messages/`, one class per operation — see [Hyperdrive Message Architecture](tracker-app/packages/hyperdrive/README.md)
- **Queue**: Database-backed Laravel queue
- **Mail**: Laravel mailable classes with queue support
- **Testing**: PHPUnit (Feature/Unit), Vitest, Playwright
- **Models**: Auto-generated via Reliese Laravel
- **Plugins**: Local plugins in `tracker-app/plugins/<slug>` (Discord, Google Sheets, HoloNet, Sentry, XenForo)

### Required XenForo add-on

When using the XenForo plugin (`tracker-app/plugins/xenforo`) for authentication, chat, and forum features, install the consolidated add-on into your XenForo instance:

- [Troop Tracker XenForo Addon](https://github.com/obsidianslicers/troop-tracker-xenforo-addons) — Addon ID: `ObsidianSlicers/TroopTracker`, requires XenForo 2.2.0+

---

## Contributor Quickstart

Get operational in under 5 minutes. All commands run from the `tracker-app/` directory.

### 1. Install

No PHP, Composer, Node or MySQL on your machine? [Docker](docs/DOCKER.md) runs the app and the tests with one command each.

```bash
# Clone and navigate to the Laravel app directory
git clone https://github.com/obsidianslicers/trooper-tracker.git
cd trooper-tracker/tracker-app

# Install PHP and JavaScript dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate
```

**Configure your `.env` file** - See [Environment Variables](docs/ENVIRONMENT_VARIABLES.md) for complete reference:
- Set database credentials
- Add OAuth keys if testing Google/XenForo login (optional for basic development)
- Queue driver defaults to `database`

### 2. Run

```bash
# Set up database with sample data
php artisan migrate
php artisan db:seed --class=OrganizationSeeder
php artisan db:seed --class=ActorSeeder

# Start development servers
composer dev
```

**Default Login:**
- Check ActorSeeder for admin credentials or create a new account
- All new accounts require admin approval (approve via database or create as active)

### 3. Test

```bash
# Run full test suite (Feature + Unit)
php artisan test --parallel   # one process per CPU core: ~10 s instead of ~1 min (ParaTest comes with composer install)

# Run with coverage report (needs PCOV or Xdebug)
#php artisan test --coverage

# Run specific test file
#php artisan test tests/Feature/Http/Controllers/Events/Pages/ShowEventControllerTest.php

# Run front-end unit tests (Vitest, tests/js)
npm test

# Run browser tests (Playwright, tests/e2e) against a seeded app
npm run build
npm run test:e2e:parallel   # or npm run test:e2e
```

**Before submitting PRs:**
- New/Updated Code must be tested
- All tests must pass: `php artisan test --parallel` and `npm test`; for page or flow changes, `npm run test:e2e` too ([docs/E2E_TESTING.md](docs/E2E_TESTING.md))
- Code must pass formatting: `composer pint:format`
- Translations must pass: `php artisan tracker:report-translations --strict`
- Follow the conventions in [CLAUDE.md](CLAUDE.md) and [docs/CODING_CONVENTIONS.md](docs/CODING_CONVENTIONS.md)
- The PR title follows the commit format in [CLAUDE.md](CLAUDE.md#commit-messages)

### Common Development Commands

```bash
# Generate base models after schema changes
php artisan code:models
php artisan fabricator:generate-factories

# Run scheduled tasks manually
php artisan tracker:send-daily-event-notifications
php artisan tracker:close-events
php artisan tracker:calculate-trooper-achievements

# Check translations (missing English lines, untranslated lines per language)
php artisan tracker:report-translations --strict

# Clear caches during development
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Documentation Guide

Read the documentation in this order for maximum comprehension efficiency.

### For New Contributors

**Start here** - Essential reading before writing code:

1. **[Architecture](docs/ARCHITECTURE.md)** - ADR, Hyperdrive Messages, migration boundaries, domain organization, testing strategy
2. **[Coding Conventions](docs/CODING_CONVENTIONS.md)** - Naming conventions, code style, architectural standards
3. **[Project Structure](docs/PROJECT_STRUCTURE.md)** - Directory layout and file organization
4. **[Database Schema](docs/DATABASE.md)** - Table reference, relationships, ERD

**Recommended for all contributors:**

5. **[Cheat Sheet](docs/CHEAT_SHEET.md)** - Quick reference for Artisan commands and workflows
6. **[Browser Tests](docs/E2E_TESTING.md)** - Playwright setup, seeded personas, writing a spec

### For Feature-Specific Work

Consult these when working on specific subsystems:

- **[Authentication](docs/AUTHENTICATION.md)** - Multi-provider auth and registration pipeline
- **[Plugins](docs/PLUGINS.md)** - Plugin hooks, slots, settings, and the bundled plugins
- **[Localization](docs/LOCALIZATION.md)** - Translations and adding a language
- **[Achievements](docs/ACHIEVEMENTS.md)** - Automatic achievement calculation, club-scoped milestones, and notifications
- **[Club Memberships](docs/CLUB_MEMBERSHIPS.md)** - Join requests, memberships, and trooper-organization data flow
- **[Membership Roles](docs/MEMBERSHIP_ROLES.md)** - Member, moderator, administrator, handler, visitor permissions
- **[Events](docs/EVENTS.md)** - Event types, signup behavior, capacity, and status rules
- **[Event Workflow](docs/EVENT_WORKFLOW.md)** - Event lifecycle, mission brief acknowledgement, and notification flow
- **[Trooper Signup Limits](docs/TROOPER_SIGNUP_LIMITS.md)** - Code-backed signup limiting scenarios
- **[Station Signups](docs/STATION_SIGNUPS.md)** - Splitting a shift into named stations with their own capacity
- **[Troop Credit](docs/TROOP_CREDIT.md)** - How event attendance credit is attributed to organizations
- **[Charity Tracking](docs/CHARITY.md)** - Charity fields, hours, funds, and report usage
- **[Galactic Academy](docs/GALACTIC_ACADEMY.md)** - Guardian/minor account rules for child-focused organizations
- **[FAQ](docs/FAQ.md)** - FAQ sections, admin management, and public rendering
- **[Shared Club Tracking](docs/SHARED_CLUB_TRACKING.md)** - Multi-club operating model and shared tracking rationale
- **[XenForo OAuth](docs/XENFORO_OAUTH.md)** - Forum integration via OAuth2

### Deployment & Tooling

- **[Environment Variables](docs/ENVIRONMENT_VARIABLES.md)** - Complete `.env` configuration reference
- **[Deployment Guide](docs/DEPLOY.md)** - Production server setup and deployment procedures
- **[Artisan Commands](docs/COMMANDS.md)** - Scheduled and manual tracker command reference
- **[Issue Fix Seeders](docs/ISSUE_MIGRATIONS.md)** - One-time data repair seeders and how to run them
- **[Florida Garrison Setup](docs/FLORIDA_GARRISON_SETUP.md)** - Florida Garrison-specific installation and migration setup
- **[Docker](docs/DOCKER.md)** - Run the app and the tests in containers
- **[Composer Dependencies](docs/COMPOSER.md)** - PHP package reference
- **[NPM Dependencies](docs/NPM.md)** - Frontend package reference

### For Contributors

- **[Contributing Guide](CONTRIBUTING.md)** - Submission workflow, PR requirements, code review process
- **[Code of Conduct](CODE_OF_CONDUCT.md)** - Community standards and enforcement

---

## Contributing

Contributions are accepted and processed with the efficiency expected of Imperial operations. Review the [Contributing Guide](CONTRIBUTING.md) for submission protocols, then consult [Coding Conventions](docs/CODING_CONVENTIONS.md) for architectural requirements. All contributions must pass automated testing and code style validation before consideration.
