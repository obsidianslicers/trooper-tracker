# Environment Variables Reference

Configuration reference for all `.env` variables in Troop Tracker.

**For OAuth setup instructions, see [AUTHENTICATION.md](AUTHENTICATION.md) and [XENFORO_OAUTH.md](XENFORO_OAUTH.md).**

---

## Application Settings

### APP_NAME
- **Purpose**: Application name displayed in UI and emails
- **Required**: Yes
- **Default**: `"Troop Tracker"`
- **Example**: `"501st Florida Garrison"`
- **When to change**: Set to your organization's name

### APP_ENV
- **Purpose**: Runtime environment
- **Required**: Yes
- **Default**: `local`
- **Values**: `local`, `staging`, `production`
- **When to change**: Set to `production` on live servers

### APP_DEBUG
- **Purpose**: Controls error display and debug toolbar
- **Required**: Yes
- **Default**: `true` (local), `false` (production)
- **When to change**: **Must be `false` in production** to prevent information disclosure

### APP_URL
- **Purpose**: Base URL for the application (used in emails, OAuth callbacks)
- **Required**: Yes
- **Example**: `https://trooptracker.example.com`
- **When to change**: Set to your domain in production

### APP_KEY
- **Purpose**: Encryption key for sessions, cookies, encrypted data
- **Required**: Yes
- **Generation**: Run `php artisan key:generate`
- **Security**: Never commit this value to version control

---

## Localization

### APP_LOCALE
- **Purpose**: Default language
- **Required**: No
- **Default**: `en`

### APP_FALLBACK_LOCALE
- **Purpose**: Fallback language if translation missing
- **Required**: No
- **Default**: `en`

### APP_FAKER_LOCALE
- **Purpose**: Locale for fake data generation in tests/seeders
- **Required**: No
- **Default**: `en_US`

---

## Troop Tracker Settings

### TRACKER_SUPPORT_GOAL
- **Purpose**: Annual support goal (displayed in UI)
- **Required**: No
- **Default**: None
- **Example**: `300`
- **When to change**: Set to your organization's funding target

### TRACKER_SUPPORT_URL
- **Purpose**: Link to donation/support page
- **Required**: No
- **Example**: `https://your-forum.com/account/upgrades`

### TRACKER_CALENDAR_TIMEZONE
- **Purpose**: Timezone for event dates and scheduling
- **Required**: Yes
- **Default**: `America/New_York`
- **When to change**: Set to your organization's timezone

### TRACKER_IMAGE_DRIVER
- **Purpose**: Image processing driver for photo uploads
- **Required**: No
- **Default**: `Intervention\Image\Drivers\Gd\Driver`
- **Values**: `Intervention\Image\Drivers\Gd\Driver` or `Intervention\Image\Drivers\Imagick\Driver`
- **When to change**: Use Imagick for better performance if available on server

---

## Database

### DB_CONNECTION
- **Purpose**: Database driver
- **Required**: Yes
- **Default**: `mysql`
- **Values**: `mysql`, `sqlite` (tests only)

### DB_HOST
- **Purpose**: Database server hostname
- **Required**: Yes
- **Default**: `localhost`

### DB_PORT
- **Purpose**: Database server port
- **Required**: Yes
- **Default**: `3306`

### DB_DATABASE
- **Purpose**: Database name
- **Required**: Yes
- **Example**: `trooptracker`

### DB_USERNAME
- **Purpose**: Database user
- **Required**: Yes
- **Security**: Use dedicated user with minimal privileges

### DB_PASSWORD
- **Purpose**: Database password
- **Required**: Yes
- **Security**: Use strong password, never commit to version control

---

## Session & Cache

### SESSION_DRIVER
- **Purpose**: Session storage driver
- **Required**: Yes
- **Default**: `database`
- **Values**: `database`, `file`, `redis`, `memcached`
- **When to change**: Use `redis` or `memcached` for better performance in production

### SESSION_LIFETIME
- **Purpose**: Session lifetime in minutes
- **Required**: Yes
- **Default**: `120`
- **When to change**: Increase for longer login sessions

### SESSION_ENCRYPT
- **Purpose**: Encrypt session data
- **Required**: No
- **Default**: `false`

### SESSION_PATH
- **Purpose**: Cookie path
- **Required**: No
- **Default**: `/`

### SESSION_DOMAIN
- **Purpose**: Cookie domain
- **Required**: No
- **Default**: `null`

### CACHE_STORE
- **Purpose**: Cache driver
- **Required**: Yes
- **Default**: `database`
- **Values**: `database`, `file`, `redis`, `memcached`
- **When to change**: Use `redis` for production

### CACHE_PREFIX
- **Purpose**: Cache key prefix (prevents conflicts in shared cache)
- **Required**: No
- **Default**: None

---

## Queue

### QUEUE_CONNECTION
- **Purpose**: Queue driver for background jobs
- **Required**: Yes
- **Default**: `sync` (local), `database` (production)
- **Values**: `sync`, `database`, `redis`, `beanstalkd`
- **When to change**: Use `database` or `redis` in production for async processing

**Note**: Run `php artisan queue:work` when using `database` or `redis` drivers.

---

## Mail

### MAIL_MAILER
- **Purpose**: Mail driver
- **Required**: Yes
- **Default**: `log` (local), `smtp` (production)
- **Values**: `smtp`, `sendmail`, `mailgun`, `postmark`, `log`
- **When to change**: Use `smtp` in production for actual email delivery

### MAIL_HOST
- **Purpose**: SMTP server hostname
- **Required**: When using SMTP
- **Example**: `smtp.mailtrap.io`

### MAIL_PORT
- **Purpose**: SMTP server port
- **Required**: When using SMTP
- **Default**: `2525` (Mailtrap), `587` (TLS), `465` (SSL)

### MAIL_USERNAME
- **Purpose**: SMTP authentication username
- **Required**: When using SMTP with auth
- **Security**: Never commit credentials to version control

### MAIL_PASSWORD
- **Purpose**: SMTP authentication password
- **Required**: When using SMTP with auth
- **Security**: Never commit credentials to version control

### MAIL_FROM_ADDRESS
- **Purpose**: Default "from" email address for all outgoing emails
- **Required**: Yes
- **Default**: `hello@example.com`
- **Example**: `noreply@trooptracker.com`
- **When to change**: Set to your organization's email address

### MAIL_FROM_NAME
- **Purpose**: Default "from" name for all outgoing emails
- **Required**: Yes
- **Default**: `Example`
- **Example**: `Troop Tracker`
- **When to change**: Set to your organization's name

### MAIL_SUBJECT_PREFIX
- **Purpose**: Prefix added to all outgoing email subject lines
- **Required**: No
- **Default**: `[Troop Tracker]`
- **Example**: `[501st FL]`
- **When to change**: Customize to easily identify emails from your organization

---

## OAuth Providers

### Google OAuth

#### GOOGLE_CLIENT_ID
- **Purpose**: Google OAuth client ID
- **Required**: For Google login
- **Setup**: Obtain from Google Cloud Console
- **See**: [AUTHENTICATION.md](AUTHENTICATION.md)

#### GOOGLE_CLIENT_SECRET
- **Purpose**: Google OAuth client secret
- **Required**: For Google login
- **Security**: Never commit to version control

#### GOOGLE_REDIRECT_URI
- **Purpose**: OAuth callback URL
- **Required**: For Google login
- **Format**: `{APP_URL}/oauth/google/callback`
- **Note**: Must match URL registered in Google Cloud Console

#### GOOGLE_MAPS_API_KEY
- **Purpose**: Google Maps API for geocoding event addresses
- **Required**: For location features
- **Setup**: Obtain from Google Cloud Console with Maps API enabled

---

### XenForo OAuth

#### XENFORO_CLIENT_ID
- **Purpose**: XenForo OAuth client ID
- **Required**: For forum-based login
- **Setup**: Created in XenForo Admin CP
- **See**: [XENFORO_OAUTH.md](XENFORO_OAUTH.md)

#### XENFORO_CLIENT_SECRET
- **Purpose**: XenForo OAuth client secret
- **Required**: For forum-based login
- **Security**: Never commit to version control

#### XENFORO_REDIRECT_URI
- **Purpose**: OAuth callback URL
- **Required**: For forum-based login
- **Format**: `{APP_URL}/oauth/xenforo/callback`
- **Note**: Must match URL registered in XenForo OAuth client settings

#### XENFORO_BASE_URL
- **Purpose**: Base URL of XenForo forum installation
- **Required**: For forum-based login
- **Format**: `https://forum.example.com` (no trailing slash)
- **Example**: `https://www.fl501st.com/boards`

#### XENFORO_NAME
- **Purpose**: Forum name displayed during OAuth flow
- **Required**: No
- **Default**: Uses APP_NAME if not set

---

## Logging

### LOG_CHANNEL
- **Purpose**: Default log channel
- **Required**: Yes
- **Default**: `stack`
- **Values**: `stack`, `single`, `daily`, `stderr`

### LOG_STACK
- **Purpose**: Channels used by stack driver
- **Required**: When using stack
- **Default**: `single`

### LOG_DEPRECATIONS_CHANNEL
- **Purpose**: Channel for deprecation warnings
- **Required**: No
- **Default**: `null`

### LOG_LEVEL
- **Purpose**: Minimum log level
- **Required**: Yes
- **Default**: `debug` (local), `error` (production)
- **Values**: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`

---

## Other Settings

### BROADCAST_CONNECTION
- **Purpose**: Broadcasting driver (not currently used)
- **Required**: No
- **Default**: `log`

### FILESYSTEM_DISK
- **Purpose**: Default filesystem disk
- **Required**: Yes
- **Default**: `local`
- **Values**: `local`, `public`, `s3`

### BCRYPT_ROUNDS
- **Purpose**: Bcrypt hashing rounds for passwords
- **Required**: No
- **Default**: `12`
- **Security**: Higher values increase security but slow down authentication

### APP_MAINTENANCE_DRIVER
- **Purpose**: Maintenance mode storage
- **Required**: No
- **Default**: `file`

---

## Example Production .env

```env
# Application
APP_NAME="Your Organization Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://trooptracker.example.com
APP_KEY=base64:YOUR_GENERATED_KEY_HERE

# Troop Tracker
TRACKER_CALENDAR_TIMEZONE=America/New_York
TRACKER_IMAGE_DRIVER=Intervention\Image\Drivers\Imagick\Driver

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=trooptracker_prod
DB_USERNAME=trooptracker_user
DB_PASSWORD=SECURE_PASSWORD_HERE

# Session & Cache
SESSION_DRIVER=redis
CACHE_STORE=redis

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD=SMTP_PASSWORD_HERE

# Google OAuth
GOOGLE_CLIENT_ID=YOUR_GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET=YOUR_GOOGLE_CLIENT_SECRET
GOOGLE_REDIRECT_URI=https://trooptracker.example.com/oauth/google/callback

# XenForo OAuth
XENFORO_CLIENT_ID=YOUR_XENFORO_CLIENT_ID
XENFORO_CLIENT_SECRET=YOUR_XENFORO_CLIENT_SECRET
XENFORO_REDIRECT_URI=https://trooptracker.example.com/oauth/xenforo/callback
XENFORO_BASE_URL=https://forum.example.com

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error
```

---

## Security Best Practices

1. **Never commit secrets**: Use `.env.example` for templates, keep actual `.env` out of version control
2. **Rotate credentials**: Change passwords and secrets periodically
3. **Environment-specific configs**: Use different credentials for local/staging/production
4. **Restrict database access**: Use dedicated database user with minimal required privileges
5. **Production settings**:
   - `APP_DEBUG=false`
   - `APP_ENV=production`
   - `LOG_LEVEL=error` or `warning`
   - Strong, unique `APP_KEY`
   - HTTPS for `APP_URL`
