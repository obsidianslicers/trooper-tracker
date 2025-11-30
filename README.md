# Troop Tracker 🚀

Troop Tracker is a scalable, immersive dashboard application designed to manage trooper assignments, moderation workflows, and hierarchical notifications across organizations, regions, and units. Built with Laravel, Blade, Bootstrap 5, HTMX, and JavaScript, it balances technical rigor with creative flair.

---

## ✨ Features

*   **Hierarchical Access Control** - Enforces strict Org → Region → Unit hierarchy
*   **Trooper Management** - Multiple roles per trooper with unique indexes
*   **Notice/Messaging System** - Bubble-up logic from units → regions → orgs
*   **Immersive Dashboard** - Themed UI with Bootstrap 5 and Blade components

---

## 🛠️ Tech Stack

*   **Backend**: Laravel 12, Blade templating
*   **Frontend**: Bootstrap 5, HTMX, JS
*   **Database**: MySQL
*   **Testing**: PHPUnit
*   **Version Control**: Git + GitHub

---

## ⚙️ Installation

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
    ```

---

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

---

## 📚 Additional Resources

*   [Code of Conduct](CODE_OF_CONDUCT.md)
*   [Cheat Sheet](CHEAT_SHEET.md)
*   [Coding Conventions](CODING_CONVENTIONS.md)
*   [Contributing Guide](CONTRIBUTING.md)
*   [VSCode Extensions](VSCODE_EXTENSIONS.md)

---

## 🤝 Contributing

We welcome contributions! Please see the [Contributing Guide](CONTRIBUTING.md) for detailed instructions on how to get started.
