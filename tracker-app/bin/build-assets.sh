# Builds the front end for deploy.sh without taking the live build away. Sourced from
# the app folder with NPM set.
#
# Vite empties its output folder before it writes, so building straight into public/build
# leaves the site without a manifest for the length of the build, and every page fails.
# build_assets builds into public/build-next instead, and swap_assets puts that in place.

ASSETS_LIVE="public/build"
ASSETS_NEXT="public/build-next"
ASSETS_OLD="public/build-old"

build_assets() {
    rm -rf "$ASSETS_NEXT"
    "$NPM" run build -- --outDir "$ASSETS_NEXT" --emptyOutDir || return 1
    [ -f "$ASSETS_NEXT/manifest.json" ]
}

swap_assets() {
    rm -rf "$ASSETS_OLD"
    if [ -d "$ASSETS_LIVE" ]; then
        mv "$ASSETS_LIVE" "$ASSETS_OLD"
    fi
    mv "$ASSETS_NEXT" "$ASSETS_LIVE"
    rm -rf "$ASSETS_OLD"
}
