#!/bin/bash
set -e

PHP="/opt/bitnami/php/bin/php"
COMPOSER="/opt/bitnami/php/bin/composer"
NPM="/usr/bin/npm"

echo "🚀 Starting Deployment..."

# 1. Build Assets
echo "📦 Building Laravel Assets..."
$NPM install
$NPM run build

# 2. Enter Maintenance Mode
echo "🚧 Taking application offline..."
$PHP artisan down --refresh=15 || true

# 3. Clear and Reset Caches
echo "🧹 Clearing and re-caching..."
$PHP artisan cache:clear
$PHP artisan route:clear
$PHP artisan config:clear
$PHP artisan view:clear

# 4. Install Composer Dependencies (IMPORTANT — you were missing this step)
echo "📦 Installing Composer dependencies..."
$COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 5. Run Migrations
echo "🗄️ Running database migrations..."
$PHP artisan migrate --force

# 6. Re-cache for Performance
echo "⚡ Re-optimizing caches..."
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

# 7. Bring Application Online
echo "✅ Bringing application online..."
$PHP artisan up

echo "🌟 Deployment Complete!"