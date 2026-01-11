# Deployment Guide

This document provides step-by-step instructions for deploying Troop Tracker to production environments.

## Server Requirements

### Minimum Requirements

- **PHP:** 8.2 or higher
- **Database:** MySQL 8.0 or higher
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **Node.js:** 18.x or higher (for asset compilation)
- **Composer:** 2.x
- **Memory:** 512MB minimum, 1GB+ recommended

### Required PHP Extensions

```bash
php -m | grep -E 'pdo|mysql|mbstring|tokenizer|xml|ctype|json|bcmath|fileinfo|openssl'
```

Required extensions:
- PDO, pdo_mysql
- mbstring
- tokenizer
- xml, ctype, json
- bcmath
- fileinfo
- openssl
- GD or Imagick (for image processing via intervention/image)

## Pre-Deployment Setup

### 1. Server Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP and required extensions
sudo apt install php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-bcmath php8.2-curl php8.2-gd php8.2-zip -y

# Install MySQL
sudo apt install mysql-server -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (via nvm recommended)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 18
nvm use 18
```

### 2. Database Setup

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE trooper_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'trooper_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON trooper_tracker.* TO 'trooper_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Application User

```bash
# Create dedicated application user
sudo adduser --disabled-password --gecos "" trooper
sudo usermod -aG www-data trooper
```

## Initial Deployment

### 1. Clone Repository

```bash
# Clone to web directory
cd /var/www
sudo git clone https://github.com/your-org/troop-tracker.git
sudo chown -R trooper:www-data troop-tracker
cd troop-tracker/tracker-app
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
npm ci
npm run build
```

### 3. Environment Configuration

```bash
# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment variables
nano .env
```

**Critical `.env` Settings:**

```env
APP_NAME="Troop Tracker"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trooper_tracker
DB_USERNAME=trooper_user
DB_PASSWORD=secure_password_here

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Queue Configuration
QUEUE_CONNECTION=database

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database

# OAuth (if using)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

XENFORO_CLIENT_ID=your_xenforo_client_id
XENFORO_CLIENT_SECRET=your_xenforo_client_secret
XENFORO_REDIRECT_URI="${APP_URL}/auth/xenforo/callback"
XENFORO_BASE_URL=https://your-forum-url.com
```

### 4. File Permissions

```bash
# Set ownership
sudo chown -R trooper:www-data /var/www/troop-tracker

# Set directory permissions
sudo find /var/www/troop-tracker -type d -exec chmod 755 {} \;
sudo find /var/www/troop-tracker -type f -exec chmod 644 {} \;

# Set storage and cache permissions
sudo chmod -R 775 /var/www/troop-tracker/tracker-app/storage
sudo chmod -R 775 /var/www/troop-tracker/tracker-app/bootstrap/cache
```

### 5. Database Migration

```bash
# Run migrations
php artisan migrate --force

# IMPORTANT: Generate base models after migrations
# DEVELOPMENT ONLY
php artisan code:models
php artisan tracker:generate-factories

# Seed database (if needed)
php artisan db:seed --force
```

### 6. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

### 7. Storage Link

```bash
# Create symbolic link for public storage
php artisan storage:link
```

## Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/troop-tracker`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    root /var/www/troop-tracker/tracker-app/public;
    index index.php index.html;

    # SSL Configuration (use certbot)
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/troop-tracker-access.log;
    error_log /var/log/nginx/troop-tracker-error.log;

    # Increase upload size for event photos
    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/troop-tracker /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

Create `/etc/apache2/sites-available/troop-tracker.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    
    DocumentRoot /var/www/troop-tracker/tracker-app/public
    
    <Directory /var/www/troop-tracker/tracker-app/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/troop-tracker-error.log
    CustomLog ${APACHE_LOG_DIR}/troop-tracker-access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite troop-tracker
sudo a2enmod rewrite ssl
sudo systemctl reload apache2
```

## Queue Workers

### Systemd Service Configuration

Create `/etc/systemd/system/troop-tracker-queue.service`:

```ini
[Unit]
Description=Troop Tracker Queue Worker
After=network.target mysql.service

[Service]
Type=simple
User=trooper
Group=www-data
Restart=always
RestartSec=5s
ExecStart=/usr/bin/php /var/www/troop-tracker/tracker-app/artisan queue:work --sleep=3 --tries=3 --max-time=3600

StandardOutput=append:/var/log/troop-tracker-queue.log
StandardError=append:/var/log/troop-tracker-queue-error.log

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable troop-tracker-queue
sudo systemctl start troop-tracker-queue
sudo systemctl status troop-tracker-queue
```

## Scheduled Tasks (Cron)

Add to crontab for `trooper` user:

```bash
sudo crontab -e -u trooper
```

Add:
```cron
# Laravel Scheduler
* * * * * cd /var/www/troop-tracker/tracker-app && php artisan schedule:run >> /dev/null 2>&1
```

**Key Scheduled Commands:**
- `tracker:send-daily-event-notifications` - Sends daily event digest emails
- `tracker:close-events` - Closes events that have ended
- `tracker:close-event-shifts` - Closes event shifts and sends completion emails

## SSL Certificate (Let's Encrypt)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx -y

# For Nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# For Apache
sudo apt install python3-certbot-apache -y
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Auto-renewal test
sudo certbot renew --dry-run
```

## Updates & Deployments

### Zero-Downtime Deployment Script

Create `deploy.sh` in repository root:

```bash
#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Navigate to app directory
cd /var/www/troop-tracker/tracker-app

# Enable maintenance mode
php artisan down --retry=60

# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Regenerate base models if schema changed
php artisan code:models

# Clear and rebuild caches
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers
sudo systemctl restart troop-tracker-queue

# Disable maintenance mode
php artisan up

echo "✅ Deployment complete!"
```

Make executable:
```bash
chmod +x deploy.sh
```

### Manual Deployment Steps

```bash
# 1. Enter maintenance mode
php artisan down

# 2. Pull latest code
git pull origin main

# 3. Update dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Run migrations (IMPORTANT: regenerate models after)
php artisan migrate --force
php artisan code:models

# 5. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 6. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart queue workers
sudo systemctl restart troop-tracker-queue

# 8. Exit maintenance mode
php artisan up
```

## Database Backup

### Automated Backup Script

Create `/usr/local/bin/backup-troop-tracker.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/troop-tracker"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="trooper_tracker"
DB_USER="trooper_user"
DB_PASS="secure_password_here"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Backup database
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/db_backup_$DATE.sql.gz"

# Backup uploaded files
tar -czf "$BACKUP_DIR/storage_backup_$DATE.tar.gz" /var/www/troop-tracker/tracker-app/storage/app

# Keep only last 7 days
find "$BACKUP_DIR" -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Add to crontab:
```cron
# Daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-troop-tracker.sh >> /var/log/troop-tracker-backup.log 2>&1
```

## Monitoring & Logging

### Log Locations

- **Application logs:** `tracker-app/storage/logs/laravel.log`
- **Nginx logs:** `/var/log/nginx/troop-tracker-*.log`
- **Apache logs:** `/var/log/apache2/troop-tracker-*.log`
- **Queue logs:** `/var/log/troop-tracker-queue.log`
- **PHP-FPM logs:** `/var/log/php8.2-fpm.log`

### Log Rotation

Create `/etc/logrotate.d/troop-tracker`:

```
/var/www/troop-tracker/tracker-app/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0640 trooper www-data
    sharedscripts
}
```

## Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong, random `APP_KEY`
- [ ] Set appropriate file permissions (755/644)
- [ ] Configure SSL/TLS certificates
- [ ] Enable HTTPS redirect
- [ ] Set security headers (X-Frame-Options, etc.)
- [ ] Use strong database passwords
- [ ] Disable directory listing
- [ ] Configure firewall (UFW)
- [ ] Keep PHP and dependencies updated
- [ ] Enable fail2ban for brute force protection
- [ ] Regular security audits with `composer audit`

## Performance Optimization

### OPcache Configuration

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### PHP-FPM Tuning

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

## Troubleshooting

### Common Issues

**Queue not processing:**
```bash
# Check queue worker status
sudo systemctl status troop-tracker-queue

# View logs
sudo journalctl -u troop-tracker-queue -f

# Restart worker
sudo systemctl restart troop-tracker-queue
```

**Permissions errors:**
```bash
# Reset permissions
sudo chown -R trooper:www-data /var/www/troop-tracker
sudo chmod -R 775 tracker-app/storage
sudo chmod -R 775 tracker-app/bootstrap/cache
```

**Cache issues:**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
```

**Database connection errors:**
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

**Base models out of sync:**
```bash
# Regenerate base models after schema changes
php artisan code:models
```

## Rollback Procedure

```bash
# 1. Enter maintenance mode
php artisan down

# 2. Revert code
git reset --hard HEAD~1
# or
git checkout <previous-commit-hash>

# 3. Rollback database
php artisan migrate:rollback

# 4. Reinstall dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 5. Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers
sudo systemctl restart troop-tracker-queue

# 7. Exit maintenance mode
php artisan up
```

## Health Checks

### Application Health Endpoint

Monitor application health:
```bash
curl https://your-domain.com/up
```

### Service Monitoring

```bash
# Check all services
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status troop-tracker-queue

# Check disk space
df -h

# Check memory
free -h

# Check MySQL status
sudo mysqladmin -p status
```

## Additional Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/12.x/deployment)
- [Laravel Forge](https://forge.laravel.com) - Automated deployment service
- [Laravel Envoyer](https://envoyer.io) - Zero-downtime deployment
- [Coding Conventions](CODING_CONVENTIONS.md) - Project coding standards
- [Authentication Flow](AUTHENTICATION.md) - Multi-provider auth setup
- [Notifications System](NOTIFICATIONS.md) - Event notification architecture
