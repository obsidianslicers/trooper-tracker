#!/bin/bash
# Checks bin/build-assets.sh with a stand-in for npm: the live build stays in place
# while the new one is made, a failed build leaves it alone, and the swap replaces it.
#
#   bash tests/deploy/build-assets-test.sh
set -Eeuo pipefail

SCRIPT="$(cd "$(dirname "$0")/../.." && pwd)/bin/build-assets.sh"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
cd "$WORK"

PASSED=0
pass() { echo "  ✅ $1"; PASSED=$((PASSED + 1)); }
fail() { echo "  ❌ $1"; exit 1; }
check() { if eval "$2"; then pass "$1"; else fail "$1"; fi; }

# Stands in for "npm run build -- --outDir <dir> --emptyOutDir". It records whether the
# live manifest was there while it ran.
fake_npm() {
    local out="$5"
    [ -f public/build/manifest.json ] && echo yes > live-during-build || echo no > live-during-build
    [ "${FAIL_BUILD:-}" = 1 ] && return 1
    mkdir -p "$out/assets"
    echo '{"new": true}' > "$out/manifest.json"
    echo new > "$out/assets/app-new.js"
}
NPM=fake_npm

# shellcheck source=../../bin/build-assets.sh
. "$SCRIPT"

mkdir -p public/build/assets
echo '{"old": true}' > public/build/manifest.json
echo old > public/build/assets/app-old.js

echo "🧪 A failed build"
check "build_assets reports the failure" "! FAIL_BUILD=1 build_assets"
check "the live build is untouched" "grep -q old public/build/manifest.json"

echo "🧪 A good build"
check "build_assets succeeds" "build_assets"
check "the live manifest was there during the build" "grep -q yes live-during-build"
check "the live build is still the old one before the swap" \
    "grep -q old public/build/manifest.json"
check "the new build is waiting in public/build-next" \
    "grep -q new public/build-next/manifest.json"

echo "🧪 The swap"
swap_assets
check "the new build is live" "grep -q new public/build/manifest.json"
check "old files are gone" "[ ! -e public/build/assets/app-old.js ]"
check "no build-next or build-old is left" "[ ! -e public/build-next ] && [ ! -e public/build-old ]"

echo "🧪 A first deploy with no live build"
rm -rf public/build
build_assets && swap_assets
check "the new build is live" "grep -q new public/build/manifest.json"

echo "All $PASSED checks passed."
