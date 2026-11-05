#!/bin/bash
# End-to-end check of how deploys treat a plugin with its own Composer packages, with
# real Composer runs. It needs network access to Packagist and takes a few minutes.
#
#   bash tests/deploy/plugin-deploy-acceptance.sh /path/to/a/copy/of/tracker-app
#
# Run it on a throwaway copy of tracker-app with its packages installed
# (composer install --no-dev): it changes that copy's vendor folder and database.
# Each "deploy" runs the same package, migration and reconcile steps as deploy.sh
# (bin/plugin-packages.sh), without git, npm or maintenance mode.
set -Eeuo pipefail

APP="$(cd "${1:?Pass the path of a copy of tracker-app}" && pwd)"
cd "$APP"

PHP="$(command -v php)"
COMPOSER_BIN="$(command -v composer)"
PLUGIN_DEPLOY_DIR="$APP/storage/app/plugin-deploy"
export DB_CONNECTION=sqlite DB_DATABASE="$APP/storage/acceptance.sqlite"
export CACHE_STORE=array QUEUE_CONNECTION=sync SESSION_DRIVER=array
export APP_ENV=testing APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
export COMPOSER_NO_AUDIT=1
# Set COMPOSER_IGNORE_PLATFORM_REQS=1 to run on a PHP older than the lock file needs.

mkdir -p "$PLUGIN_DEPLOY_DIR"
# shellcheck source=../../bin/plugin-packages.sh
. "$APP/bin/plugin-packages.sh"

PASSED=0
pass() { echo "  ✅ $1"; PASSED=$((PASSED + 1)); }
fail() { echo "  ❌ $1"; exit 1; }
check() { if eval "$2"; then pass "$1"; else fail "$1"; fi; }

artisan() { "$PHP" artisan "$@" --no-interaction; }
plugins_unmet() { artisan plugins:unmet 2>/dev/null | sort || true; }
installed() {
    "$PHP" -r 'require "vendor/autoload.php"; $p = $argv[1];
        echo Composer\InstalledVersions::isInstalled($p)
            ? Composer\InstalledVersions::getPrettyVersion($p) : "none";' "$1"
}
sql() {
    "$PHP" -r '$db = new PDO("sqlite:".$argv[1]);
        foreach ($db->query($argv[2]) ?: [] as $row) { echo implode("|", array_map("strval", array_values(array_unique($row)))), "\n"; }' \
        "$DB_DATABASE" "$1"
}
column() {
    sql "select $2 from tt_plugins where slug = '$1'"
}

# The same steps as deploy.sh. $2 and $3 name functions that stand in for "git reset"
# to the new release and back to the previous one.
# Returns 1 where deploy.sh would stop and put the previous release back.
deploy() {
    echo "🚀 Deploy: $1"
    local new_release="${2:-true}" previous_release="${3:-true}"
    local unmet_before
    unmet_before="$(plugins_unmet)"
    "$new_release"
    backup_plugin_packages
    artisan plugins:packages --write >/dev/null 2>&1 || true

    if ! install_packages >/dev/null; then
        "$previous_release"
        restore_previous_packages >/dev/null 2>&1
        echo "  ⏪ stopped: no working set of packages"
        return 1
    fi

    local newly_broken
    newly_broken="$(comm -13 <(echo "$unmet_before") <(plugins_unmet) | sed '/^$/d')"
    if [ -n "$newly_broken" ]; then
        "$previous_release"
        restore_previous_packages >/dev/null 2>&1
        echo "  ⏪ stopped: $newly_broken would stop working"
        return 1
    fi

    artisan migrate --force >/dev/null
    local before
    before="$(sha1sum "$PLUGIN_REQUIREMENTS")"
    RECONCILE="$(artisan plugins:reconcile)"
    echo "$RECONCILE" | sed 's/^/    /'

    if [ "$before" != "$(sha1sum "$PLUGIN_REQUIREMENTS")" ]; then
        backup_plugin_packages
        if ! install_packages >/dev/null; then
            restore_previous_packages >/dev/null 2>&1
        fi
        artisan migrate --force >/dev/null
        RECONCILE="$(artisan plugins:reconcile)"
        echo "$RECONCILE" | sed 's/^/    /'
    fi
}

# A custom addon, as an administrator would copy it onto the server.
write_addon() {
    local folder="$1" version="$2" link="$3" extra="${4:-}"
    mkdir -p "$folder/src" "$folder/database/migrations"
    cat >"$folder/plugin.json" <<JSON
{"slug": "weather", "name": "Weather", "version": "$version",
 "namespace": "WeatherPlugin", "provider": "WeatherPlugin\\\\Provider",
 "settings": [{"key": "city", "label": "City", "type": "text"}]}
JSON
    cat >"$folder/composer.json" <<JSON
{"name": "acme/weather", "require": {"psr/link": "$link"$extra}}
JSON
    cat >"$folder/src/Provider.php" <<'PHP'
<?php

namespace WeatherPlugin;

class Provider extends \App\Plugins\PluginServiceProvider {}
PHP
    cat >"$folder/database/migrations/2026_01_01_000001_reports.php" <<'PHP'
<?php

use Illuminate\Support\Facades\Schema;

return new class extends Illuminate\Database\Migrations\Migration {
    public function up(): void { Schema::create('tt_weather_reports', fn ($t) => $t->id()); }
    public function down(): void { Schema::dropIfExists('tt_weather_reports'); }
};
PHP
}

add_label_migration() {
    cat >"$1/database/migrations/2026_01_01_000002_label.php" <<'PHP'
<?php

use Illuminate\Support\Facades\Schema;

return new class extends Illuminate\Database\Migrations\Migration {
    public function up(): void {
        Schema::table('tt_weather_reports', fn ($t) => $t->string('label')->nullable());
    }
    public function down(): void {
        Schema::table('tt_weather_reports', fn ($t) => $t->dropColumn('label'));
    }
};
PHP
}

UPLOADS="$APP/storage/app/plugins"
rm -f "$DB_DATABASE" composer.plugins.json composer.plugins.lock
rm -rf "$UPLOADS/weather" "$UPLOADS/.pending" "$UPLOADS/.previous"
rm -f "$PLUGIN_DEPLOY_DIR"/requirements*.json
touch "$DB_DATABASE"
echo "📦 Start from the app's own packages"
install_app_packages >/dev/null 2>&1
artisan migrate --force >/dev/null
CORE_BEFORE="$(installed laravel/framework)"

echo "📦 Install a custom addon that needs psr/link, which the app does not have"
write_addon "$UPLOADS/weather" 1.0.0 '^1.0'
check "psr/link is not installed yet" '[ "$(installed psr/link)" = none ]'
artisan plugins:enable weather >/dev/null
check "enabling waits for the deploy" '[ "$(column weather is_enable_pending)" = 1 ]'

deploy "1: installs its package and turns it on"
check "psr/link 1.x installed" '[[ "$(installed psr/link)" == 1.* ]]'
check "weather is on and active" \
    '[ "$(column weather is_enabled)" = 1 ] && [ "$(column weather status)" = active ]'
check "its migration ran" '[ "$(sql "select count(*) from tt_weather_reports")" = 0 ]'
sql "insert into tt_weather_reports (id) values (7)"
sql "update tt_plugins set settings = '{\"city\":\"Tampa\"}' where slug = 'weather'"
check "core packages unchanged" '[ "$(installed laravel/framework)" = "$CORE_BEFORE" ]'
mkdir -p "$PLUGIN_DEPLOY_DIR/releases/one" && cp composer.plugins.json composer.plugins.lock "$PLUGIN_DEPLOY_DIR/releases/one/"

LOCK_HASH="$(sha1sum composer.plugins.lock)"
deploy "2: nothing changed"
check "nothing to modify" 'grep -q "Nothing to modify in lock file" "$PLUGIN_COMPOSER_LOG"'
check "lock file unchanged" '[ "$(sha1sum composer.plugins.lock)" = "$LOCK_HASH" ]'
check "reconcile left every plugin alone" 'echo "$RECONCILE" | grep -q "Every plugin is as it was"'

echo "📦 Update the addon: psr/link ^2.0, a new package and a new migration"
write_addon "$UPLOADS/.pending/weather" 1.1.0 '^2.0' ', "webmozart/assert": "^1.11"'
add_label_migration "$UPLOADS/.pending/weather"
deploy "3: compatible update with new packages"
check "psr/link moved to 2.x" '[[ "$(installed psr/link)" == 2.* ]]'
check "webmozart/assert installed" '[ "$(installed webmozart/assert)" != none ]'
check "weather updated to 1.1.0" '[ "$(column weather installed_version)" = 1.1.0 ]'
check "its new migration ran" 'sql "select label from tt_weather_reports" >/dev/null 2>&1'
check "its data was kept" '[ "$(sql "select id from tt_weather_reports")" = 7 ]'
check "its settings were kept" '[ "$(column weather settings)" = "{\"city\":\"Tampa\"}" ]'
check "core packages unchanged" '[ "$(installed laravel/framework)" = "$CORE_BEFORE" ]'

echo "📦 A broken update: it needs psr/link ^99, which does not exist"
write_addon "$UPLOADS/.pending/weather" 1.2.0 '^99.0'
deploy "4: failed dependency update"
check "the deploy kept the last working packages" \
    'grep -q "\"mode\":\"last-good\"" "$PLUGIN_DEPLOY_DIR/composer-result.json"'
check "psr/link still 2.x" '[[ "$(installed psr/link)" == 2.* ]]'
check "weather still 1.1.0, on and active" \
    '[ "$(column weather installed_version)" = 1.1.0 ] && [ "$(column weather status)" = active ]'
check "the waiting update is shown" '[[ "$(column weather last_error)" == *"1.2.0 is uploaded and waits"* ]]'
rm -rf "$UPLOADS/.pending/weather"

echo "📦 A release that would leave the running addon without what it needs"
cp "$UPLOADS/weather/composer.json" "$APP/storage/weather-composer.json"
breaking_release() {
    write_addon "$UPLOADS/weather" 1.1.0 '^99.0' ', "webmozart/assert": "^1.11"'
    add_label_migration "$UPLOADS/weather"
}
previous_release() { cp "$APP/storage/weather-composer.json" "$UPLOADS/weather/composer.json"; }
check "the deploy stops and keeps the previous release" \
    '! deploy "5: would break an enabled plugin" breaking_release previous_release'
check "psr/link still 2.x" '[[ "$(installed psr/link)" == 2.* ]]'
check "weather can still run" '[ -z "$(plugins_unmet)" ]'
check "weather still on" '[ "$(column weather is_enabled)" = 1 ]'


echo "📦 Roll back to release one's packages"
cp "$PLUGIN_DEPLOY_DIR/releases/one/"composer.plugins.* "$PLUGIN_BACKUP_DIR/"
restore_previous_packages >/dev/null 2>&1
check "psr/link back to 1.x" '[[ "$(installed psr/link)" == 1.* ]]'
deploy "6: forward again after the rollback"
check "psr/link 2.x again" '[[ "$(installed psr/link)" == 2.* ]]'
check "weather active" '[ "$(column weather status)" = active ]'

echo "📦 Disable the addon"
artisan plugins:disable weather >/dev/null
deploy "7: disabled"
check "its packages were removed" '[ "$(installed psr/link)" = none ]'
check "it stays disabled" '[ "$(column weather is_enabled)" = 0 ] && [ "$(column weather status)" = disabled ]'
deploy "8: still disabled"
check "it stays disabled" '[ "$(column weather is_enabled)" = 0 ]'

artisan plugins:enable weather >/dev/null
deploy "9: enabled again"
check "packages back and weather active" \
    '[[ "$(installed psr/link)" == 2.* ]] && [ "$(column weather status)" = active ]'
check "its data and settings survived" \
    '[ "$(sql "select id from tt_weather_reports")" = 7 ] && [ "$(column weather settings)" = "{\"city\":\"Tampa\"}" ]'
check "core packages unchanged" '[ "$(installed laravel/framework)" = "$CORE_BEFORE" ]'

echo "🌟 $PASSED checks passed"
