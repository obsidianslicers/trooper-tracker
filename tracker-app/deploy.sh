#!/bin/bash
set -euo pipefail

APP_DIR="/home/bitnami/trooper-tracker/tracker-app"
PHP="/opt/bitnami/php/bin/php"
COMPOSER="/opt/bitnami/php/bin/composer"
NPM="/usr/bin/npm"

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
