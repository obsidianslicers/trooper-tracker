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

# One deploy at a time, so two never run Composer or migrations together.
exec 9>"$APP_DIR/storage/framework/deploy.lock"
if ! flock -n 9; then
    echo "❌ Another deployment is already running."
    exit 1
fi

# The last release that deployed cleanly, and the one before it, for rollback.
PLUGIN_DEPLOY_DIR="$APP_DIR/storage/app/plugin-deploy"
mkdir -p "$PLUGIN_DEPLOY_DIR/releases"
TARGET="origin/main"

if [ "${1:-}" = "--rollback" ]; then
    if [ ! -f "$PLUGIN_DEPLOY_DIR/previous-good-release" ]; then
        echo "❌ No earlier good release is recorded to roll back to."
        exit 1
    fi
    TARGET="$(cat "$PLUGIN_DEPLOY_DIR/previous-good-release")"
    echo "⏪ Rolling back to $TARGET"
fi

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
git reset --hard "$TARGET"
RELEASE="$(git rev-parse HEAD)"

if [ -f "$PLUGIN_DEPLOY_DIR/releases/$RELEASE.lock" ]; then
    # Rolling back (or redeploying) a release: use the plugin packages it had.
    cp "$PLUGIN_DEPLOY_DIR/releases/$RELEASE.lock" composer.plugins.lock
fi

# Plugins that bring their own Composer packages stay out of the app's composer.json.
# The app saves what they need (storage/app/plugin-deploy/requirements.json), and
# bin/plugin-composer builds composer.plugins.json from it: the app's composer.json plus
# those packages, with every core package held at this release's composer.lock. Its
# lock, composer.plugins.lock, stays on the server, so plugin packages keep their
# versions between deploys and an unchanged deploy changes nothing. If that fails,
# the app's own packages are installed as usual and the plugins that needed the new
# packages wait, with the error shown on the Plugins page.
save_composer_result() {
    "$PHP" -r 'file_put_contents($argv[1], json_encode(["ok" => $argv[2] === "1",
        "mode" => $argv[3], "error" => $argv[4] ?? null, "at" => date("c")]));' \
        "$PLUGIN_DEPLOY_DIR/composer-result.json" "$@"
}

install_app_packages() {
    COMPOSER=composer.json "$PHP" "$COMPOSER" install --no-interaction --prefer-dist --optimize-autoloader --no-dev
}

install_packages() {
    echo "📦 Installing Composer dependencies..."
    local status=0
    PLUGIN_UPDATES="$("$PHP" bin/plugin-composer prepare)" || status=$?

    if [ "$status" -eq 10 ]; then
        install_app_packages
        save_composer_result 1 plain
        return
    fi

    if [ "$status" -ne 0 ]; then
        install_app_packages
        save_composer_result 0 fallback "Could not prepare the plugin packages."
        return
    fi

    echo "📦 Including plugin packages: $(echo $PLUGIN_UPDATES)"
    cp composer.plugins.lock "$PLUGIN_DEPLOY_DIR/composer.plugins.lock.before"
    local log="$PLUGIN_DEPLOY_DIR/composer.log"

    # --no-scripts: a plugin's packages never run install scripts. The app's own
    # scripts run with dump-autoload, from the app's composer.json.
    # shellcheck disable=SC2086 # one argument per package
    if COMPOSER=composer.plugins.json "$PHP" "$COMPOSER" update $PLUGIN_UPDATES \
            --with-dependencies --minimal-changes --no-dev --no-scripts \
            --no-interaction --prefer-dist >"$log" 2>&1 \
        && COMPOSER=composer.plugins.json "$PHP" "$COMPOSER" dump-autoload \
            --optimize --no-dev >>"$log" 2>&1; then
        save_composer_result 1 managed
        return
    fi

    echo "⚠️ Plugin packages could not be installed; installing the app's own packages."
    tail -n 20 "$log" || true
    cp "$PLUGIN_DEPLOY_DIR/composer.plugins.lock.before" composer.plugins.lock
    install_app_packages
    save_composer_result 0 fallback "$(tail -n 15 "$log")"
}

install_packages

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

echo "🧩 Reconciling plugins..."
# Runs plugin migrations, turns on plugins whose packages are now in, and records
# each plugin's state. If that changed which packages plugins need (for example a
# plugin was enabled before this deploy), install them now and reconcile again.
REQUIREMENTS="$PLUGIN_DEPLOY_DIR/requirements.json"
reconcile_plugins() {
    "$PHP" artisan plugins:reconcile \
        || echo "⚠️ Plugin reconcile reported a problem. See Admin > Plugins."
}
BEFORE="$(sha1sum "$REQUIREMENTS" 2>/dev/null || true)"
reconcile_plugins

if [ "$BEFORE" != "$(sha1sum "$REQUIREMENTS" 2>/dev/null || true)" ]; then
    install_packages
    "$PHP" artisan migrate --force
    reconcile_plugins
fi

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

# Remember this release, and its plugin packages, as the last good one.
if [ -f composer.plugins.lock ]; then
    cp composer.plugins.lock "$PLUGIN_DEPLOY_DIR/releases/$RELEASE.lock"
fi
LAST_GOOD="$(cat "$PLUGIN_DEPLOY_DIR/last-good-release" 2>/dev/null || true)"
if [ -n "$LAST_GOOD" ] && [ "$LAST_GOOD" != "$RELEASE" ]; then
    echo "$LAST_GOOD" > "$PLUGIN_DEPLOY_DIR/previous-good-release"
fi
echo "$RELEASE" > "$PLUGIN_DEPLOY_DIR/last-good-release"
# Keep the plugin locks of the last ten releases.
ls -1t "$PLUGIN_DEPLOY_DIR/releases/"*.lock 2>/dev/null | tail -n +11 | xargs -r rm -f

echo "========================================"
echo "🌟 Deployment Complete!"
echo "Version: $APP_VERSION"
echo "========================================"