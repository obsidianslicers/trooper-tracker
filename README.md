# Troop Tracker

**Troop Tracker** is the Empire’s official operations dashboard, engineered to impose order upon trooper assignments, moderation workflows, and hierarchical communications across organizations, regions, and units. Forged with Laravel, Blade, Bootstrap 5, HTMX, and Alpine‑driven JavaScript, it delivers the precision, discipline, and ruthless efficiency expected of any system operating under Imperial authority.


<!--
[![Laravel Tests](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/laravel-tests.yml/badge.svg)](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/laravel-tests.yml)

-->
[![Laravel Style](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/pint.yml/badge.svg)](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/pint.yml)

[![Laravel Stan](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/phpstan.yml/badge.svg)](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/phpstan.yml)

---

## Status Report: Development Proceeds at the Empire's Pace

This project remains under active development, which is to say it currently exists in a state of sanctioned chaos. Features may appear, disappear, or behave unpredictably without prior notice, as is their prerogative during this phase of imperial construction. Should you encounter bugs, inconsistencies, or architectural decisions that defy mortal comprehension, rest assured they are merely temporary artifacts of progress. Proceed with caution, submit issues with appropriate deference, and remember: stability will arrive when it is commanded to arrive, and not a moment sooner.

Progress continues at a pace deemed acceptable by the Empire. New features, refinements, and the occasional miracle will be deployed as they reach a state worthy of consumption. Garrison Liasons are encouraged to return in approximately one month to witness the next phase of sanctioned advancement. Until then, patience is not only advised — it is expected.

---

## Architecture

Troop Tracker follows **Action-Domain-Responder (ADR)** with **Command/Query Separation** via **MagicBus**. Controllers orchestrate, handlers execute business logic, responses render results.

**Key Patterns:**
- **MagicBus**: Convention-based dispatcher routes Commands (writes) and Queries (reads) to handlers
- **Feature Organization**: Business logic grouped by domain in `app/Features/` (Events, Troopers, Organizations, Reports, Notices, Changes)
- **Auto-Generated Models**: Base models in `app/Models/Base/` (never edit), extended in `app/Models/`
- **HTMX + Alpine**: Progressive enhancement for dynamic UI without full page reloads
- **Queue Processing**: Background jobs orchestrate handlers via MagicBus

**[Read full architecture documentation →](docs/ARCHITECTURE.md)**

---

## Repository Structure

### Top-Level Folders

**`tracker-app/`** - The Laravel application (run all commands from here)
- Complete Laravel 12.x source, tests, and dependencies
- See [Project Structure](docs/PROJECT_STRUCTURE.md) for internal organization

**`docs/`** - Project documentation
- Architecture, database schema, authentication flows, coding conventions
- See [Documentation Guide](#documentation-guide) for reading order

**Root Files** - `README.md`, `CODE_OF_CONDUCT.md`, `CONTRIBUTING.md`, GitHub Actions

---

## Features

### Core Capabilities

*   **Hierarchical Access Control**: Strict Organization → Region → Unit permissions with automatic inheritance and scoped visibility via authorization policies
*   **Trooper Management**: Multi‑organization membership, role‑based permissions (member/moderator/administrator), notification preferences, costume tracking, and achievement badges
*   **Event & Shift Management**: Full event lifecycle (draft/open/closed/cancelled), multi‑shift scheduling, organization-specific invitations, trooper signup tracking (going/tentative/unavailable)
*   **Real‑Time HTMX Interactions**: Instant UI updates for sign‑ups, cancellations, costume changes, and shift displays without full page reloads
*   **Smart Notifications**: Configurable frequency (never/instant/daily), event creation/cancellation emails, daily digest aggregation
*   **Awards & Recognition**: Organization-based awards with frequency controls (once/monthly/yearly), multi-recipient support
*   **Notice System**: Organization-scoped announcements with read tracking and type-based styling (info/warning/alert)
*   **Event Photo Gallery**: Photo uploads with trooper tagging, large/thumbnail variants, administrative photo flags

### Developer Experience

*   **MagicBus Command/Query Pattern**: Automatic handler resolution, Commands for writes, Queries for reads
*   **Feature-Organized Code**: Domain logic grouped by business area
*   **Component-Driven Blade**: PHP 8.2+ with server-rendered templates
*   **Progressive Enhancement**: Bootstrap 5.2x + HTMX 2.x + Alpine 3.x
*   **Auto-Generated Models**: MySQL with Reliese Laravel base model generation
*   **Comprehensive Testing**: Feature tests (Controllers/Jobs/Commands), Unit tests (Handlers/Services)
*   **Policy-Based Authorization**: Scoped access control for all resources
*   **Audit Trail**: Polymorphic change tracking with trooper stamps

---

## Tech Stack

- **PHP 8.2+** with strict types and scalar type hints
- **Laravel 12.x** with Breeze authentication
- **Database**: MySQL (production), SQLite (testing)
- **Frontend**: Blade templates + Bootstrap 5.2x + HTMX 2.x + Alpine 3.x
- **Queue**: Database-backed Laravel queue
- **Mail**: Laravel mailable classes with queue support
- **Testing**: PHPUnit with Feature/Unit test separation
- **Models**: Auto-generated via Reliese Laravel

---

## Contributor Quickstart

Get operational in under 5 minutes. All commands run from the `tracker-app/` directory.

### 1. Install

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

**Configure your `.env` file:**
- Set database credentials (MySQL recommended, SQLite works for local dev)
- Add OAuth keys if testing Google/XenForo login (optional for basic development)
- Queue driver defaults to `database` (no additional setup needed)

### 2. Run

```bash
# Set up database with sample data
php artisan migrate --seed

# Start development servers (requires 3 terminals)
php artisan serve           # Terminal 1: Laravel app (http://localhost:8000)
npm run dev                 # Terminal 2: Vite asset compilation
php artisan queue:work      # Terminal 3: Background job processing
```

**Default Login:**
- Check seeder output for admin credentials or create a new account
- All new accounts require admin approval (approve via database or create as active)

### 3. Test

```bash
# Run full test suite (Feature + Unit)
php artisan test

# Run with coverage report
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/Http/Controllers/Events/EventDisplayControllerTest.php

# Verify code style
./vendor/bin/pint --test
```

**Before submitting PRs:**
- All tests must pass: `php artisan test`
- Code must pass linting: `./vendor/bin/pint`
- Follow conventions in `docs/CODING_CONVENTIONS.md`

### Common Development Commands

```bash
# Generate base models after schema changes
php artisan code:models
php artisan tracker:generate-factories

# Run scheduled tasks manually
php artisan tracker:send-daily-event-notifications
php artisan tracker:close-events
php artisan tracker:calculate-trooper-achievements

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

1. **[Architecture](docs/ARCHITECTURE.md)** - ADR, MagicBus, Command/Query patterns, domain organization, testing strategy
2. **[Coding Conventions](docs/CODING_CONVENTIONS.md)** - Naming conventions, code style, architectural standards
3. **[Project Structure](docs/PROJECT_STRUCTURE.md)** - Directory layout and file organization
4. **[Database Schema](docs/DATABASE.md)** - Table reference, relationships, ERD

**Recommended for all contributors:**

5. **[Cheat Sheet](docs/CHEAT_SHEET.md)** - Quick reference for Artisan commands and workflows
6. **[VSCode Extensions](docs/VSCODE_EXTENSIONS.md)** - Recommended editor setup

### For Feature-Specific Work

Consult these when working on specific subsystems:

- **[Authentication](docs/AUTHENTICATION.md)** - Multi-provider auth and registration pipeline
- **[Notifications](docs/NOTIFICATIONS.md)** - Event notification system (instant/daily/cancellations)
- **[XenForo OAuth](docs/XENFORO_OAUTH.md)** - Forum integration via OAuth2

### Deployment & Tooling

- **[Deployment Guide](docs/DEPLOY.md)** - Production server setup and deployment procedures
- **[Composer Dependencies](docs/COMPOSER.md)** - PHP package reference
- **[NPM Dependencies](docs/NPM.md)** - Frontend package reference

### For Contributors

- **[Contributing Guide](CONTRIBUTING.md)** - Submission workflow, PR requirements, code review process
- **[Code of Conduct](CODE_OF_CONDUCT.md)** - Community standards and enforcement

---

## Contributing

Contributions are accepted and processed with the efficiency expected of Imperial operations. Review the [Contributing Guide](CONTRIBUTING.md) for submission protocols, then consult [Coding Conventions](docs/CODING_CONVENTIONS.md) for architectural requirements. All contributions must pass automated testing and code style validation before consideration.
