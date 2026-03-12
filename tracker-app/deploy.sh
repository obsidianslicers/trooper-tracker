#!/bin/bash

# 1. Set Path for Node.js (Ensures npm/node work in this session)
#export PATH=/opt/alt/alt-nodejs24/root/usr/bin:$PATH

echo "🚀 Starting Deployment..."

# 2. Build Assets
echo "📦 Building Laravel Assets..."
npm install
npm run build

# 3. Enter Maintenance Mode
echo "🚧 Taking application offline..."
php artisan down --refresh=15

# 4. Clear and Reset Caches
echo "🧹 Clearing and re-caching..."
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

# 5. Run Migrations
echo "🗄️  Running database migrations..."
# --force is required to run migrations in production mode
php artisan migrate --force

# 6. Re-cache for Performance
echo "⚡ Re-optimizing caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Bring Application Online
echo "✅ Bringing application online..."
php artisan up

echo "🌟 Deployment Complete!"
