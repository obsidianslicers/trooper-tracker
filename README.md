# Troop Tracker

**Troop Tracker** is the Empire’s official operations dashboard, engineered to impose order upon trooper assignments, moderation workflows, and hierarchical communications across organizations, regions, and units. Forged with Laravel, Blade, Bootstrap 5, HTMX, and Alpine‑driven JavaScript, it delivers the precision, discipline, and ruthless efficiency expected of any system operating under Imperial authority.


<!--
[![Laravel Tests](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/laravel-tests.yml/badge.svg)](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/laravel-tests.yml)

[![Laravel Style](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/laravel-pint-pr.yml/badge.svg)](https://github.com/obsidianslicers/trooper-tracker/actions/workflows/laravel-pint-pr.yml)
-->

---

## Status Report: Development Proceeds at the Empire's Pace

This project remains under active development, which is to say it currently exists in a state of sanctioned chaos. Features may appear, disappear, or behave unpredictably without prior notice, as is their prerogative during this phase of imperial construction. Should you encounter bugs, inconsistencies, or architectural decisions that defy mortal comprehension, rest assured they are merely temporary artifacts of progress. Proceed with caution, submit issues with appropriate deference, and remember: stability will arrive when it is commanded to arrive, and not a moment sooner.

Progress continues at a pace deemed acceptable by the Empire. New features, refinements, and the occasional miracle will be deployed as they reach a state worthy of consumption. Garrison Liasons are encouraged to return in approximately one month to witness the next phase of sanctioned advancement. Until then, patience is not only advised — it is expected.

---

## For Developers Entering the War Room

This codebase is structured for maintainability, testability, and the occasional emergency refactor ordered from high command. Expect Commands, Queries, Handlers, and a strict separation of concerns. Expect HTMX to fire without warning. Expect Alpine components to behave with military precision. Above all, expect the unexpected — the Empire innovates aggressively.

---

## Features

*   **Hierarchical Access Control**: Strict Organization → Region → Unit permissions with automatic inheritance and scoped visibility.
*   **Trooper Profiles & Roles**: Multi‑organization membership, role‑based permissions, notification preferences, and costume metadata.
*   **Event & Shift Management**: Full event lifecycle, multi‑shift scheduling, handler vs trooper capacity rules, and automatic waitlist promotion.
*   **Real‑Time HTMX Interactions**: Instant UI updates for sign‑ups, cancellations, costume changes, and shift displays without full page reloads.
*   **CQRS + MagicBus Architecture**: Commands for domain actions, Queries for read models, and clean separation of concerns across the entire app.
*   **Notification & Messaging System**: Bubble‑up logic for org → region → unit notices, plus event‑driven email workflows (e.g., next‑in‑line promotions).
*   **Themed, Component‑Driven UI**: Bootstrap 5 + Blade Components + Alpine.js  for interactive selectors, filters, and modals.
*   **Transaction‑Safe Upload Pipeline**: Atomic image uploads with Intervention v3, driver selection, thumbnail generation, and orphan‑prevention.
*   **Domain‑Driven Frontend Architecture**: Namespaced Alpine components mirroring backend domains, zero global pollution, and expressive semantic APIs.
*   **Calendar & Event Discovery Tools**: Filterable event listings, organization pickers, costume‑type filters.

---

## Tech Stack

*   **Backend**: Laravel 12.x, Blade templating
*   **Frontend**: Bootstrap 5.2x, HTMX 2.x, Alpine 3.x, JS
*   **Database**: MySQL
*   **Testing**: PHPUnit
*   **Version Control**: Git + GitHub

---

## Installation

1.  Clone the repository:
    ```bash
    git clone https://github.com/your-org/troop-tracker.git
    cd troop-tracker
    ```

2.  Install dependencies:
    ```bash
    composer install
    npm install && npm run build
    ```

3.  Configure environment:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  Run migrations:
    ```bash
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
