# Troop Tracker

Troop Tracker is a scalable, immersive dashboard application designed to manage trooper assignments, moderation workflows, and hierarchical notifications across organizations, regions, and units. Built with Laravel, Blade, Bootstrap 5, HTMX, and JavaScript, it balances technical rigor with creative flair.

---

## Status Report: Development Proceeds at the Empire's Pace

This project remains under active development, which is to say it currently exists in a state of sanctioned chaos. Features may appear, disappear, or behave unpredictably without prior notice, as is their prerogative during this phase of imperial construction. Should you encounter bugs, inconsistencies, or architectural decisions that defy mortal comprehension, rest assured they are merely temporary artifacts of progress. Proceed with caution, submit issues with appropriate deference, and remember: stability will arrive when it is commanded to arrive, and not a moment sooner.

Progress continues at a pace deemed acceptable by the Empire. New features, refinements, and the occasional miracle will be deployed as they reach a state worthy of consumption. Garrison Liasons are encouraged to return in approximately one month to witness the next phase of sanctioned advancement. Until then, patience is not only advised — it is expected.

---

## Features

*   **Hierarchical Access Control** - Enforces strict Org → Region → Unit hierarchy
*   **Trooper Management** - Multiple roles per trooper with unique indexes
*   **Notice/Messaging System** - Bubble-up logic from units → regions → orgs
*   **Immersive Dashboard** - Themed UI with Bootstrap 5 and Blade components

---

## Tech Stack

*   **Backend**: Laravel 12.x, Blade templating
*   **Frontend**: Bootstrap 5.2x, HTMX 2.x, Alpine 2.x, JS
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
