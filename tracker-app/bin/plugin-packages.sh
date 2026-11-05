# Installs the app's Composer packages plus the ones its plugins need, for deploy.sh.
# Sourced from the app folder with PHP, COMPOSER_BIN and PLUGIN_DEPLOY_DIR set.
#
# The app saves what the plugins need ($PLUGIN_DEPLOY_DIR/requirements.json), and
# bin/plugin-composer builds composer.plugins.json from it: the app's composer.json plus
# those packages, with every core package held at this release's composer.lock. Its lock,
# composer.plugins.lock, stays on the server, so plugin packages keep their versions
# between deploys and an unchanged deploy changes nothing.
#
# Call backup_plugin_packages first. install_packages then tries, in order:
#   1. the packages plugins need now;
#   2. the last set that installed cleanly (requirements.installed.json), so plugins that
#      were working keep their packages, and new or updated ones wait;
# and if both fail it puts the previous composer.plugins.* back and returns 1, leaving
# the caller to restore the previous release (restore_previous_packages).

PLUGIN_REQUIREMENTS="$PLUGIN_DEPLOY_DIR/requirements.json"
PLUGIN_REQUIREMENTS_INSTALLED="$PLUGIN_DEPLOY_DIR/requirements.installed.json"
PLUGIN_BACKUP_DIR="$PLUGIN_DEPLOY_DIR/before"
PLUGIN_COMPOSER_LOG="$PLUGIN_DEPLOY_DIR/composer.log"

save_composer_result() {
    "$PHP" -r 'file_put_contents($argv[1], json_encode(["ok" => $argv[2] === "1",
        "mode" => $argv[3], "error" => $argv[4] ?? null, "at" => date("c")]));' \
        "$PLUGIN_DEPLOY_DIR/composer-result.json" "$@"
}

# The app's own packages only, from its composer.json and composer.lock.
install_app_packages() {
    COMPOSER=composer.json "$PHP" "$COMPOSER_BIN" install --no-interaction --prefer-dist \
        --optimize-autoloader --no-dev
}

# Keep the composer.plugins.* the running release uses, to put back on failure.
backup_plugin_packages() {
    rm -rf "$PLUGIN_BACKUP_DIR"
    mkdir -p "$PLUGIN_BACKUP_DIR"
    for file in composer.plugins.json composer.plugins.lock; do
        if [ -f "$file" ]; then
            cp "$file" "$PLUGIN_BACKUP_DIR/$file"
        fi
    done
}

# Install exactly what the backed-up composer.plugins.* say (or the app's own packages
# when there were none). Used to go back to the previous working set.
restore_previous_packages() {
    rm -f composer.plugins.json composer.plugins.lock
    if [ ! -f "$PLUGIN_BACKUP_DIR/composer.plugins.lock" ]; then
        install_app_packages
        return
    fi

    cp "$PLUGIN_BACKUP_DIR/composer.plugins.json" "$PLUGIN_BACKUP_DIR/composer.plugins.lock" .
    COMPOSER=composer.plugins.json "$PHP" "$COMPOSER_BIN" install --no-interaction \
        --prefer-dist --no-dev --no-scripts
    COMPOSER=composer.plugins.json "$PHP" "$COMPOSER_BIN" dump-autoload --optimize --no-dev
}

# Install the app's packages plus the plugin packages in $1. Returns non-zero on failure.
install_with_requirements() {
    local requirements="$1"
    local status=0
    local updates
    updates="$("$PHP" bin/plugin-composer prepare "$requirements")" || status=$?

    if [ "$status" -eq 10 ]; then
        rm -f composer.plugins.json composer.plugins.lock
        install_app_packages >>"$PLUGIN_COMPOSER_LOG" 2>&1
        return
    fi

    if [ "$status" -ne 0 ]; then
        echo "Could not prepare the plugin packages." >>"$PLUGIN_COMPOSER_LOG"
        return 1
    fi

    echo "📦 Including plugin packages: $(echo $updates)"
    # --no-scripts: a plugin's packages never run install scripts. The app's own scripts
    # run with dump-autoload, from the app's composer.json. Only the Composer plugins the
    # app allows (config.allow-plugins) may run.
    # shellcheck disable=SC2086 # one argument per package
    COMPOSER=composer.plugins.json "$PHP" "$COMPOSER_BIN" update $updates \
        --with-dependencies --minimal-changes --no-dev --no-scripts \
        --no-interaction --prefer-dist >>"$PLUGIN_COMPOSER_LOG" 2>&1 \
    && "$PHP" bin/plugin-composer verify >>"$PLUGIN_COMPOSER_LOG" 2>&1 \
    && COMPOSER=composer.plugins.json "$PHP" "$COMPOSER_BIN" dump-autoload \
        --optimize --no-dev >>"$PLUGIN_COMPOSER_LOG" 2>&1
}

install_packages() {
    echo "📦 Installing Composer dependencies..."
    : >"$PLUGIN_COMPOSER_LOG"

    if [ ! -f "$PLUGIN_REQUIREMENTS" ]; then
        echo '{"packages":{}}' >"$PLUGIN_REQUIREMENTS"
    fi

    if install_with_requirements "$PLUGIN_REQUIREMENTS"; then
        cp "$PLUGIN_REQUIREMENTS" "$PLUGIN_REQUIREMENTS_INSTALLED"
        save_composer_result 1 current
        return 0
    fi

    local error
    error="$(grep -v -e 'root/super user' -e 'COMPOSER_ALLOW_SUPERUSER' \
        -e 'disabled for safety' -e 'COMPOSER_IGNORE_PLATFORM_REQS' "$PLUGIN_COMPOSER_LOG" \
        | tail -n 15)"
    echo "⚠️ The plugin packages could not be installed:"
    echo "$error"

    if [ -f "$PLUGIN_REQUIREMENTS_INSTALLED" ]; then
        echo "📦 Keeping the plugin packages that last installed cleanly..."
        rm -f composer.plugins.json composer.plugins.lock
        if [ -f "$PLUGIN_BACKUP_DIR/composer.plugins.lock" ]; then
            cp "$PLUGIN_BACKUP_DIR/composer.plugins.lock" composer.plugins.lock
        fi
        if install_with_requirements "$PLUGIN_REQUIREMENTS_INSTALLED"; then
            save_composer_result 0 last-good "$error"
            return 0
        fi
    fi

    echo "⚠️ Could not install a working set of packages for this release."
    save_composer_result 0 failed "$error"
    return 1
}
