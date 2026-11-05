#!/bin/bash
set -Eeuo pipefail

APP_DIR="/home/admin/trooper-tracker/tracker-app"

# Load NVM for GitHub Actions SSH deployments
export NVM_DIR="$HOME/.nvm"
if [ -s "$NVM_DIR/nvm.sh" ]; then
    . "$NVM_DIR/nvm.sh"
fi

# Show errors and restore application if deployment fails
on_error() {
    local exit_code=$?
    local line_number="$1"

    echo "❌ Deployment failed at line $line_number (exit $exit_code)"

    if [ "${MAINTENANCE_STARTED:-false}" = true ]; then
        echo "🔄 Attempting to restore application..."
        "$PHP" artisan up || true
    fi

    exit "$exit_code"
}

trap 'on_error "$LINENO"' ERR

# Verify required dependencies
for cmd in php composer node npm git; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "❌ Missing required command: $cmd"
        exit 1
    fi
done

PHP="$(command -v php)"
COMPOSER="$(command -v composer)"
NPM="$(command -v npm)"

cd "$APP_DIR"

echo "========================================"
echo "🚀 Starting Trooper Tracker Deployment"
echo "========================================"

echo "Current dir: $(pwd)"
echo "PHP: $PHP"
echo "Composer: $COMPOSER"
echo "Node: $(node --version)"
echo "NPM: $(npm --version)"

echo "📥 Syncing code with GitHub..."
git fetch origin
git reset --hard origin/main

echo "📦 Installing Composer dependencies..."
"$PHP" "$COMPOSER" install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev

# Plugins that bring their own Composer packages stay out of the app's composer.json.
# The app lists the packages to require: bundled plugins that are on or waiting to be
# turned on, and Composer plugins added on the Plugins page. git reset above undoes the
# previous deploy's require, so they are required again each time.
COMPOSER_PLUGINS="$("$PHP" artisan plugins:packages 2>/dev/null \
    | grep -E '^[a-z0-9._-]+/[a-z0-9._-]+(:.+)?$' | tr '\n' ' ' || true)"
COMPOSER_PLUGINS="${COMPOSER_PLUGINS% }"

if [ -n "$COMPOSER_PLUGINS" ]; then
    echo "📦 Installing plugin packages: $COMPOSER_PLUGINS"
    # shellcheck disable=SC2086 # one argument per package
    "$PHP" "$COMPOSER" require $COMPOSER_PLUGINS \
        --no-interaction \
        --prefer-dist \
        --update-no-dev \
        --no-scripts
    "$PHP" "$COMPOSER" dump-autoload --optimize --no-dev
fi

echo "📦 Building Laravel Assets..."

# Raise Node memory limit for build steps
export NODE_OPTIONS="--max-old-space-size=2048"

# Use lockfile for consistent deployments
if [ -f package-lock.json ]; then
    "$NPM" ci
else
    "$NPM" install
fi

"$NPM" run build

echo "🚧 Taking application offline..."
MAINTENANCE_STARTED=true
"$PHP" artisan down --refresh=15

echo "🧹 Clearing caches..."
"$PHP" artisan cache:clear
"$PHP" artisan route:clear
"$PHP" artisan config:clear
"$PHP" artisan view:clear

echo "🗄️ Running database migrations..."
"$PHP" artisan migrate --force

echo "🧩 Finishing plugin upgrades..."
"$PHP" artisan plugins:upgrade \
    || echo "⚠️ A plugin upgrade failed and was rolled back. See Admin > Plugins and the logs."
"$PHP" artisan plugins:enable-pending \
    || echo "⚠️ A plugin waiting to be turned on is still off. See Admin > Plugins and the logs."

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
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

echo "✅ Bringing application online..."
"$PHP" artisan up
MAINTENANCE_STARTED=false

echo "✅ Restarting queues..."
"$PHP" artisan queue:restart

# Not needed right now
# echo "💰 Recalculating trooper achievements and donation totals..."
# "$PHP" artisan tracker:calculate-trooper-achievements

echo "========================================"
echo "🌟 Deployment Complete!"
echo "Version: $APP_VERSION"
echo "========================================"