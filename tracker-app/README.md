# Troop Tracker — Laravel app

This folder is the Troop Tracker Laravel application. Start with the [root README](../README.md):
it covers installing, running and testing the app, the plugin system, and where the
documentation lives.

Quick reference, from this folder:

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
composer dev                    # app, queue and Vite dev server

php artisan test --parallel     # PHPUnit
npm test                        # Vitest
npm run build && npm run test:e2e   # Playwright
composer pint:format            # formatting
```

Conventions for contributors, people and AI assistants alike, are in [CLAUDE.md](../CLAUDE.md).
Optional integrations live in [plugins/](plugins/), and [docs/PLUGINS.md](../docs/PLUGINS.md)
explains them.
