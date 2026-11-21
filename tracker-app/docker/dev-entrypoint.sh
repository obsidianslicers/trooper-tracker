#!/bin/sh
# Starts the dev stack in the `app` container: the same processes as `composer dev`.
set -e
cd /app

composer install --no-interaction --no-progress
# npm ci, not npm install, which would rewrite package-lock.json on your machine. Only when the
# lockfile changed since the last start, because npm ci starts node_modules from scratch.
if ! cmp -s package-lock.json node_modules/.installed-lock.json; then
    npm ci --no-audit --no-fund
    cp package-lock.json node_modules/.installed-lock.json
fi

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force
fi

# PHP's own server, started in public/ as Laravel's router script expects, instead of
# `php artisan serve`, which drops most environment variables (such as compose.yaml's DB_HOST)
# before they reach the app. LARAVEL_SAIL makes the Vite plugin listen on 0.0.0.0 but write
# http://localhost:<port> to public/hot, which the browser can reach.
exec npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" --names=server,queue,vite \
    "cd public && PHP_CLI_SERVER_WORKERS=4 php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php" \
    "php artisan queue:listen --tries=1" \
    "LARAVEL_SAIL=1 npm run dev -- --port ${VITE_PORT:-5173} --strictPort"
