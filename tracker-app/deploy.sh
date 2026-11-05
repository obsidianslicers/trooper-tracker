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
COMPOSER_BIN="$(command -v composer)"
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
PREVIOUS_RELEASE="$(git rev-parse HEAD)"

if [ "${1:-}" = "--rollback" ]; then
    if [ ! -f "$PLUGIN_DEPLOY_DIR/previous-good-release" ]; then
        echo "❌ No earlier good release is recorded to roll back to."
        exit 1
    fi
    TARGET="$(cat "$PLUGIN_DEPLOY_DIR/previous-good-release")"
    echo "⏪ Rolling back to $TARGET"
fi

# A deploy that was cut off (lost connection, server restart) is simply redone. The
# release that was running before it is the one to go back to if this one fails.
if [ -f "$PLUGIN_DEPLOY_DIR/deploy-in-progress" ]; then
    echo "⚠️ The last deploy did not finish. Redoing it."
    PREVIOUS_RELEASE="$(cat "$PLUGIN_DEPLOY_DIR/deploy-in-progress")"
else
    echo "$PREVIOUS_RELEASE" > "$PLUGIN_DEPLOY_DIR/deploy-in-progress"
fi

# Enabled plugins that can't run (requirements not met), one slug per line.
plugins_unmet() {
    "$PHP" artisan plugins:unmet 2>/dev/null | sort || true
}

echo "========================================"
echo "🚀 Starting Trooper Tracker Deployment"
echo "========================================"

echo "Current dir: $(pwd)"
echo "PHP: $PHP"
echo "Composer: $COMPOSER_BIN"
echo "Node: $(node --version)"
echo "NPM: $(npm --version)"

UNMET_BEFORE="$(plugins_unmet)"

echo "📥 Syncing code with GitHub..."
git fetch origin
git reset --hard "$TARGET"
RELEASE="$(git rev-parse HEAD)"

# shellcheck source=bin/plugin-packages.sh
. "$APP_DIR/bin/plugin-packages.sh"

# Go back to the release that was running, with the packages it had, without ever
# taking the site down. Nothing was migrated yet.
abort_to_previous_release() {
    echo "⏪ Putting back release $PREVIOUS_RELEASE and its packages."
    git reset --hard "$PREVIOUS_RELEASE"
    restore_previous_packages
    rm -f "$PLUGIN_DEPLOY_DIR/deploy-in-progress"
    echo "❌ Deployment stopped: $1 The site is still on the previous release."
    exit 1
}

backup_plugin_packages

# The release may change what its plugins need (a bundled plugin's composer.json).
"$PHP" artisan plugins:packages --write >/dev/null 2>&1 \
    || echo "⚠️ Could not refresh the plugin packages list; using the saved one."

if [ -f "$PLUGIN_DEPLOY_DIR/releases/$RELEASE/composer.plugins.lock" ]; then
    # Rolling back (or redeploying) a release: start from the plugin packages it had.
    cp "$PLUGIN_DEPLOY_DIR/releases/$RELEASE/"composer.plugins.* .
    cp "$PLUGIN_DEPLOY_DIR/releases/$RELEASE/requirements.installed.json" \
        "$PLUGIN_DEPLOY_DIR/requirements.installed.json"
fi

if ! install_packages; then
    abort_to_previous_release "No working set of Composer packages could be installed."
fi

# A plugin that worked before this deploy must still have what it needs after it.
NEWLY_BROKEN="$(comm -13 <(echo "$UNMET_BEFORE") <(plugins_unmet) | sed '/^$/d')"
if [ -n "$NEWLY_BROKEN" ]; then
    abort_to_previous_release "These enabled plugins would stop working: $(echo $NEWLY_BROKEN)."
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

echo "🧩 Reconciling plugins..."
# Runs plugin migrations, applies plugin updates that were waiting for their packages,
# turns on plugins whose packages are now in, and records each plugin's state. If
# that changed which packages plugins need (for example a plugin was enabled before
# this deploy), install them now and reconcile again. If that second install fails,
# the packages from the first one are put back, so nothing that works now breaks.
reconcile_plugins() {
    "$PHP" artisan plugins:reconcile \
        || echo "⚠️ Plugin reconcile reported a problem. See Admin > Plugins."
}
BEFORE="$(sha1sum "$PLUGIN_REQUIREMENTS" 2>/dev/null || true)"
reconcile_plugins

if [ "$BEFORE" != "$(sha1sum "$PLUGIN_REQUIREMENTS" 2>/dev/null || true)" ]; then
    backup_plugin_packages
    if ! install_packages; then
        restore_previous_packages
    fi
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
rm -rf "$PLUGIN_DEPLOY_DIR/releases/$RELEASE"
mkdir -p "$PLUGIN_DEPLOY_DIR/releases/$RELEASE"
if [ -f composer.plugins.lock ]; then
    cp composer.plugins.json composer.plugins.lock "$PLUGIN_DEPLOY_DIR/releases/$RELEASE/"
fi
cp "$PLUGIN_REQUIREMENTS_INSTALLED" "$PLUGIN_DEPLOY_DIR/releases/$RELEASE/" 2>/dev/null || true
LAST_GOOD="$(cat "$PLUGIN_DEPLOY_DIR/last-good-release" 2>/dev/null || true)"
if [ -n "$LAST_GOOD" ] && [ "$LAST_GOOD" != "$RELEASE" ]; then
    echo "$LAST_GOOD" > "$PLUGIN_DEPLOY_DIR/previous-good-release"
fi
echo "$RELEASE" > "$PLUGIN_DEPLOY_DIR/last-good-release"
rm -f "$PLUGIN_DEPLOY_DIR/deploy-in-progress"
# Keep the plugin packages of the last ten releases.
ls -1td "$PLUGIN_DEPLOY_DIR/releases/"*/ 2>/dev/null | tail -n +11 | xargs -r rm -rf

echo "========================================"
echo "🌟 Deployment Complete!"
echo "Version: $APP_VERSION"
echo "========================================"