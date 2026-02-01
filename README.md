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

This codebase follows **Action-Domain-Responder (ADR)** with **Command/Query Separation** enforced by the **MagicBus** pattern. All business logic lives in isolated, testable handlers organized by domain.

### Core Architectural Principles

**Action-Domain-Responder (ADR)**
- **Action**: Thin, invokable controllers validate input and orchestrate via MagicBus
- **Domain**: Command/Query handlers in `app/Features/` contain all business logic
- **Responder**: Controllers format handler results into HTTP responses (Blade views, JSON, redirects)

**MagicBus Command/Query Separation**
- **Commands**: Write operations that change state (create, update, delete)
- **Queries**: Read operations that fetch data without side effects
- **Handlers**: Auto-resolved by convention (`CreateEventCommand` → `CreateEventCommandHandler`)
- **Dispatching**: Controllers, Jobs, Console Commands dispatch through `MagicBus::send()`

**Domain Organization**

Business logic is organized by feature under `app/Features/`:
- `Events/` - Event and shift management (Commands + Queries)
- `Troopers/` - Trooper profiles, membership, achievements (Commands + Queries)
- `Organizations/` - Organization hierarchy and management (Commands + Queries)
- `Reports/` - Reporting and analytics (Queries only)
- `Notices/` - Notice creation and tracking (Commands + Queries)
- `Changes/` - Audit trail and change history (Queries only)

**Data Layer**
- **Base Models**: Auto-generated from schema via Reliese Laravel (`app/Models/Base/` - never edit)
- **Extended Models**: Custom methods, scopes, accessors in `app/Models/`
- **Migrations**: All schema changes tracked in `database/migrations/`
- **Factories**: Generated test data factories in `database/factories/`

**Authorization & Validation**
- **Policies**: Resource authorization in `app/Policies/` (all use `HasTrooperPermissionsTrait`)
- **Rules**: Custom validation rules in `app/Rules/` organized by feature area
- **Middleware**: Standard Laravel auth middleware with custom Trooper guards

**Frontend Architecture**
- **Blade Templates**: Server-rendered views with component-based structure
- **HTMX 2.x**: Dynamic UI updates without full page reloads
- **Alpine 3.x**: Client-side reactivity for interactive components
- **Bootstrap 5.2x**: UI framework with custom Imperial styling

**Background Processing**
- **Jobs**: Queue jobs orchestrate handlers via MagicBus (`app/Jobs/`)
- **Commands**: Artisan commands orchestrate handlers via MagicBus (`app/Console/Commands/`)
- **Queue Driver**: Database-backed queue with worker processes

**Testing Strategy**
- **Feature Tests**: Controllers, Jobs, Commands (full HTTP/queue/console cycle)
- **Unit Tests**: Command/Query handlers, policies, validation rules (isolated business logic)
- **Test Database**: SQLite in-memory for fast, isolated execution

---

## How This Repository Is Structured

The repository uses a monorepo structure with separation between application code and project documentation.

### Top-Level Folders

**`tracker-app/`** - The Laravel application (this is where you work)
- Complete Laravel 12.x application with all source code, tests, and dependencies
- Run all `composer`, `artisan`, and `npm` commands from this directory
- See [Project Structure](#project-structure) below for internal organization

**`docs/`** - Project documentation (read this to understand the system)
- Architecture guides, database schema, authentication flows
- Coding conventions, testing strategy, development workflows
- See [Documentation Guide](#documentation-guide) for reading order

**`sql-scripts/`** - Database utility scripts
- Migration cleanup scripts and database maintenance queries

**Root Configuration Files**
- `README.md` - This file (architectural overview and quickstart)
- `CODE_OF_CONDUCT.md` - Community guidelines
- `CONTRIBUTING.md` - Contribution workflow and standards
- GitHub Actions workflows in `.github/`

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

### Architecture & Developer Experience

*   **MagicBus Command/Query Pattern**: Commands for writes, Queries for reads, automatic handler resolution by convention
*   **Feature-Organized Code**: Domain logic grouped by business area (Events, Troopers, Organizations, Reports, Notices, Changes)
*   **Themed, Component‑Driven PHP 8.2+, Blade templating
*   **Frontend**: Bootstrap 5.2x, HTMX 2.x, Alpine 3.x, JavaScript
*   **Database**: MySQL with Reliese Laravel model generation
*   **Testing**: PHPUnit (Feature tests for Controllers/Jobs/Commands, Unit tests for Handlers/Services)
*   **Queue**: Laravel queue system with database driver
*   **Mail**: Laravel mailable classes with queue support
*   **Authentication**: Laravel Breeze with multi-provider OAuth (Google, XenForo)orization**: Policy-based access control for all resources (Troopers, Events, Organizations, Notices, Awards)
*   **Custom Validation Rules**: Feature-organized validation rules for complex business logic
*   **Audit Trail System**: Polymorphic change tracking via `ModelChange`, trooper stamps (created_id/updated_id/deleted_id)
*   **Multi-Provider Auth**: Email, Google OAuth, XenForo OAuth with unified registration pipeline

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

## Project Structure

```
tracker-app/
├── app/
│   ├── Bus/                    # MagicBus command/query dispatcher
│   ├── Features/               # Domain-organized Commands & Queries
│   │   ├── Events/
│   │   ├── Troopers/
│   │   ├── Organizations/
│   │   ├── Reports/
│   │   ├── Notices/
│   │   └── Changes/
│   ├── Http/Controllers/       # Thin ADR controllers
│   ├── Models/                 # Extended Eloquent models
│   │   └── Base/              # Auto-generated (DO NOT EDIT)
│   ├── Policies/               # Authorization policies
│   ├── Rules/                  # Custom validation rules
│   ├── Services/               # Standalone services
│   ├── Jobs/                   # Queue jobs
│   └── Console/Commands/       # Artisan commands
├── docs/                       # Project documentation
├── resources/views/            # Blade templates
├── routes/web/                 # Feature-organized routes
└── tests/
    ├── Feature/                # HTTP, Job, Command tests
    └── Unit/                   # Handler, Service, Policy tests
```

## Documentation Guide

Read the documentation in this order for maximum comprehension efficiency.

### For New Contributors

**Start here** - Essential reading before writing code:

1. **[Coding Conventions](docs/CODING_CONVENTIONS.md)** - Architecture patterns (ADR, MagicBus, Command/Query), naming conventions, testing strategy. Read this first.
2. **[Project Structure](docs/PROJECT_STRUCTURE.md)** - Directory organization, Features layout, component reference. Understand where everything lives.
3. **[Database Schema](docs/DATABASE.md)** - Complete table reference with relationships and ERD. Know your data model.

**Optional but recommended:**

4. **[Cheat Sheet](docs/CHEAT_SHEET.md)** - Quick reference for common Artisan commands and workflows.
5. **[VSCode Extensions](docs/VSCODE_EXTENSIONS.md)** - Recommended editor setup for optimal development experience.

### For Feature-Specific Work

Consult these when working on specific subsystems:

- **[Authentication Flow](docs/AUTHENTICATION.md)** - Multi-provider auth (Email, Google OAuth, XenForo OAuth) and registration pipeline
- **[Notifications](docs/NOTIFICATIONS.md)** - Event notification system (instant, daily digest, cancellations)

### For Contributors

- **[Contributing Guide](CONTRIBUTING.md)** - Submission workflow, PR requirements, code review process
- **[Code of Conduct](CODE_OF_CONDUCT.md)** - Community standards and enforcement

---

## Contributing

Contributions are accepted and processed with the efficiency expected of Imperial operations. Review the [Contributing Guide](CONTRIBUTING.md) for submission protocols, then consult [Coding Conventions](docs/CODING_CONVENTIONS.md) for architectural requirements. All contributions must pass automated testing and code style validation before consideration.
