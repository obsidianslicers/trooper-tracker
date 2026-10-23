# Artisan Command Reference

Quick reference for common Laravel Artisan commands.

## Code Generation (`make:*`)

Scaffold new classes with `make` commands:

| Command | Description |
|---|---|
| `php artisan make:model Trooper -mf` | Creates a `Trooper` model (`-m` also creates a migration, `-f` creates a factory). |
| `php artisan make:controller LoginSubmitController --invokable` | Creates a single-action controller, perfect for the ADR pattern. |
| `php artisan make:request StoreTrooperRequest` | Creates a new form request class for validation in `app/Http/Requests`. |
| `php artisan make:migration create_squads_table` | Creates a new database migration file. |
| `php artisan make:factory SquadFactory` | Creates a new model factory. |
| `php artisan make:seeder UnitSeeder` | Creates a new database seeder class. |
| `php artisan make:policy TrooperPolicy --model=Trooper` | Creates a new authorization policy class. |
| `php artisan make:rule UniqueIdentifierRule` | Creates a new custom validation rule. |
| `php artisan make:command SendNotifications` | Creates a new Artisan console command. |
| `php artisan make:test UserAuthenticationTest` | Creates a new feature test file in `tests/Feature`. |
| `php artisan make:test TrooperRepositoryTest --unit` | Creates a new unit test file in `tests/Unit`. |
| `php artisan code:models --table=units` | (From `reliese/laravel`) Generates a model from an existing `units` table. |
| `php artisan code:models` | (From `reliese/laravel`) Generates all class models. |
| `php artisan fabricator:generate-factories` | Generates all base factory classes. |

## Custom Application Commands

Troop Tracker includes several custom Artisan commands for maintenance and automation:

| Command | Description |
|---|---|
| `php artisan tracker:send-daily-event-notifications` | Sends daily event digest emails to troopers with pending notifications. |
| `php artisan tracker:close-events` | Auto-closes events after their end date. |
| `php artisan tracker:close-event-shifts` | Auto-closes shifts after completion. |
| `php artisan tracker:calculate-trooper-achievements` | Recalculates trooper stats and achievement badges. |
| `php artisan tracker:calculate-trooper-achievements --without-notifications` | Recalculates achievements for large backfills without milestone notification jobs. |
| `php artisan tracker:synchronize-organizations` | Syncs with external organization systems. |
| `php artisan fabricator:generate-factories` | Generates factory classes from base models. |

## Database Migrations & Seeding

These commands are used to manage your database schema and test data.

| Command | Description |
|---|---|
| `php artisan migrate` | Runs all outstanding database migrations. |
| `php artisan migrate:fresh` | Drops all tables and re-runs all migrations from scratch. |
| `php artisan migrate:fresh --seed` | Drops all tables, re-runs migrations, and then runs the database seeders. |
| `php artisan migrate:rollback` | Rolls back the last batch of migrations. |
| `php artisan migrate:rollback --step=3` | Rolls back the last 3 batches of migrations. |
| `php artisan db:seed` | Runs all database seeders. |
| `php artisan db:seed --class=TrooperSeeder` | Runs a specific seeder class. |

## Testing

Use these commands to run your PHPUnit test suite.

| Command | Description |
|---|---|
| `php artisan test` | Runs the entire test suite (feature and unit). |
| `php artisan test --filter=UserAuthenticationTest` | Runs all tests within a specific file. |
| `php artisan test --filter=test_user_can_log_in` | Runs a specific test method by name. |

## Development & Debugging

| Command | Description |
|---|---|
| `php artisan tinker` | Starts an interactive shell (REPL) to run arbitrary code in your application. |
| `php artisan route:list` | Lists all registered routes in your application. |
| `php artisan config:clear` | Clears the configuration cache. Run this if your `.env` changes aren't taking effect. |
| `php artisan cache:clear` | Flushes the application cache. |
| `php artisan serve` | Starts the local development server (usually at `http://127.0.0.1:8000`). |
| `npm run dev` | Compiles frontend assets (CSS, JS) and watches for changes using Vite. |

## Deployment (to Production)

**See [ENVIRONMENT_VARIABLES.md](ENVIRONMENT_VARIABLES.md) for production `.env` configuration.**

Production deploys run automatically via GitHub Actions on every push to `main`
(`.github/workflows/deploy.yml` SSHes into the server and runs `tracker-app/deploy.sh`).
See [DEPLOY.md](DEPLOY.md#updates--deployments) for details.

To deploy by hand, run the script on the server:

```bash
/home/admin/trooper-tracker/tracker-app/deploy.sh
```

Current contents of `tracker-app/deploy.sh`:

```bash
#!/bin/bash
set -euo pipefail

APP_DIR="/home/admin/trooper-tracker/tracker-app"
PHP="${PHP:-$(command -v php)}"
COMPOSER="${COMPOSER:-$(command -v composer)}"
NPM="${NPM:-$(command -v npm)}"

cd "$APP_DIR"

echo "Current dir: $(pwd)"
echo "PHP: $PHP"
echo "Composer: $COMPOSER"
echo "NPM: $NPM"

echo "🚀 Starting Deployment..."

echo "📥 Syncing code with GitHub..."
git fetch origin
git reset --hard origin/main

echo "📦 Installing Composer dependencies..."
$PHP $COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "📦 Building Laravel Assets..."
# Raise Node memory limit for build steps
export NODE_OPTIONS="--max-old-space-size=2048"
$NPM install
$NPM run build

echo "🚧 Taking application offline..."
$PHP artisan down --refresh=15 || true

echo "🧹 Clearing caches..."
$PHP artisan cache:clear
$PHP artisan route:clear
$PHP artisan config:clear
$PHP artisan view:clear

echo "🗄️ Running database migrations..."
$PHP artisan migrate --force

# --- VERSION SYSTEM ---
echo "📝 Updating APP_VERSION in .env..."
COMMIT_HASH=$(git rev-parse --short HEAD)
COMMIT_DATE=$(git log -1 --format="%cd" --date=format:"%y%m%d")
APP_VERSION="build-${COMMIT_HASH}-${COMMIT_DATE}"

if grep -q "^APP_VERSION=" .env; then
    sed -i "s|^APP_VERSION=.*|APP_VERSION=\"$APP_VERSION\"|" .env
else
    echo "APP_VERSION=\"$APP_VERSION\"" >> .env
fi
echo "🔹 Application version set to: $APP_VERSION"
# -----------------------------

echo "⚡ Optimizing..."
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo "✅ Bringing application online..."
$PHP artisan up

echo "✅ Restarting queues..."
$PHP artisan queue:restart

# Not needed right now
# echo "💰 Recalculating trooper achievements and donation totals..."
# $PHP artisan tracker:calculate-trooper-achievements

echo "🌟 Deployment Complete!"
```
