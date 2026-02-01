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

## For Developers Entering the War Room

This codebase is structured for maintainability, testability, and the occasional emergency refactor ordered from high command. The architecture follows **Action-Domain-Responder (ADR)** with **Command/Query Separation** via **MagicBus**. Controllers are thin orchestrators, Commands handle writes, Queries handle reads, and Handlers contain the business logic. Expect HTMX to fire without warning. Expect Alpine components to behave with military precision. Above all, expect the unexpected — the Empire innovates aggressively.

### Architecture at a Glance

- **MagicBus**: Convention-based command/query dispatcher (Message → MessageHandler)
- **Features by Domain**: Commands & Queries organized under `app/Features/` (Events, Troopers, Organizations, Reports, Notices, Changes)
- **Invokable Controllers**: Single-action controllers following ADR pattern
- **Auto-Generated Models**: Base models in `app/Models/Base/` (never edit), extended models in `app/Models/`
- **Policies & Rules**: Authorization via policies, custom validation rules organized by feature
- **Transactional Handlers**: Database transactions via `ShouldBeTransactional` trait
- **Deferred Execution**: Background processing via `ShouldRunAfterResponse` trait

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

## Tech Stack/tracker-app
    ```

2.  Install dependencies:
    ```bash
    composer install
    npm install
    ```

3.  Configure environment:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    
    Update `.env` with your database credentials and OAuth provider keys (Google, XenForo).

4.  Run migrations and seeders:
    ```bash
    php artisan migrate --seed
    ```

5.  Generate base models (if schema changes):
    ```bash
    php artisan code:models
    php artisan tracker:generate-factories
    ```

6.  Start the development environment:
    ```bash
    # Terminal 1: Laravel development server
    php artisan serve
    
    # Terminal 2: Vite asset compilation
    npm run dev
    
    # Terminal 3: Queue worker (for background jobs)
    php artisan queue:work
3.  Configure environment:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```full test suite:
```bash
php artisan test
```

Run specific test types:
```bash
# FKey Commands

```bash
# Daily notification emails
php artisan tracker:send-daily-event-notifications

# Auto-close completed events and shifts
php artisan tracker:close-events
php artisan tracker:close-event-shifts

# Recalculate trooper achievements and badges
php artisan tracker:calculate-trooper-achievements

# Sync with external organization systems
php artisan tracker:synchronize-organizations

# Code formatting
./vendor/bin/pint
```

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

## Documentation

*   **[Project Structure](docs/PROJECT_STRUCTURE.md)** - Directory organization and component reference
*   **[Coding Conventions](docs/CODING_CONVENTIONS.md)** - Architecture patterns, MagicBus, ADR, testing strategy
*   **[Database Schema](docs/DATABASE.md)** - Complete table reference with ERD
*   **[Authentication Flow](docs/AUTHENTICATION.md)** - Multi-provider auth and registration pipeline
*   **[Notifications](docs/NOTIFICATIONS.md)** - Event notification system architecture
*   **[Cheat Sheet](docs/CHEAT_SHEET.md)** - Common Artisan commands
*   **[VSCode Extensions](docs/VSCODE_EXTENSIONS.md)** - Recommended extensions for development
*   **[Code of Conduct](CODE_OF_CONDUCT.md)** - Community guidelines
*   **[Contributing Guide](CONTRIBUTING.md)** - How to contribute
php artisan test tests/Feature/Http/Controllers/Events/EventDisplayControllerTest.php

# With coverage
php artisan test --coverage
```

### Test Organization

- **Feature Tests**: Full HTTP request/response cycle, job orchestration, command execution
- **Unit Tests**: Business logic in Command/Query handlers, validation rules, policies
- **Test Database**: SQLite in-memory for fast, isolated tests ```bash
    php artisan migrate
    ```

5.  Start the development server:
    ```bash
    php artisan serve
    npm run dev
    ```

---

## Testing

Run the test suite:
```bash
php artisan test
```

---

## Additional Resources

*   [Code of Conduct](CODE_OF_CONDUCT.md)
*   [Cheat Sheet](CHEAT_SHEET.md)
*   [Coding Conventions](CODING_CONVENTIONS.md)
*   [Contributing Guide](CONTRIBUTING.md)
*   [VSCode Extensions](VSCODE_EXTENSIONS.md)

---

## Contributing

We welcome contributions! Please see the [Contributing Guide](CONTRIBUTING.md) for detailed instructions on how to get started.
