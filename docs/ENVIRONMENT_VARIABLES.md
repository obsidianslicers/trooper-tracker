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

### APP_TIMEZONE
- **Purpose**: Default application timezone for date/time handling
- **Required**: No
- **Default**: `UTC`
- **Example**: `America/New_York`
- **When to change**: Set to your organization's timezone

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

### TRACKER_EXCEPTION_EMAIL_ENABLED
- **Purpose**: Enable or disable admin exception notification emails for unhandled 5xx errors
- **Required**: No
- **Default**: `false`
- **Values**: `true`, `false`
- **When to change**: Set to `true` in production when you want admins emailed on server errors
- **Note**: This toggles sending only; throttle behavior remains unchanged

### TRACKER_SUPERVISOR_WARN_MINUTES
- **Purpose**: Minutes since the last queue worker heartbeat before the System Check page shows a WARN status
- **Required**: No
- **Default**: `3`
- **When to change**: Raise if your queue has bursty, low-frequency traffic and false WARNs are noisy

### TRACKER_SUPERVISOR_DOWN_MINUTES
- **Purpose**: Minutes since the last queue worker heartbeat before it's considered down — drives both the System Check FAIL status and the admin down-alert email
- **Required**: No
- **Default**: `10`
- **When to change**: Tune to how quickly you want to be alerted vs. tolerating brief worker restarts/deploys

### TRACKER_SUPERVISOR_RENOTIFY_MINUTES
- **Purpose**: Minimum minutes between repeat down-alert emails while the outage continues
- **Required**: No
- **Default**: `60`
- **When to change**: Lower to be reminded more often during a prolonged outage

### TRACKER_SUPERVISOR_EMAIL_ENABLED
- **Purpose**: Enable or disable admin email alerts when the queue worker/Supervisor process goes down (and recovery emails when it comes back)
- **Required**: No
- **Default**: `false`
- **Values**: `true`, `false`
- **When to change**: Set to `true` in production if you want admins emailed when Supervisor stops keeping the queue worker alive
- **Note**: The System Check page shows worker health regardless of this setting; this only toggles the email alerts

### CONTACT_EMAIL
- **Purpose**: Email address shown as a contact fallback on the FAQ page
- **Required**: No
- **Default**: None (contact section is hidden when unset)
- **Example**: `support@your-garrison.com`
- **When to change**: Set to your organization's support address to let members reach out when the FAQ doesn't answer their question

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
- **Required**: For forum-based login and API integration
- **Format**: `https://forum.example.com` (no trailing slash)
- **Example**: `https://www.fl501st.com/boards`

#### XENFORO_NAME
- **Purpose**: Forum name displayed during OAuth flow
- **Required**: No
- **Default**: Uses APP_NAME if not set

#### XENFORO_API_KEY
- **Purpose**: XenForo API key used for forum automation and custom add-on endpoints
- **Required**: Yes for full XenForo integration
- **Used for**:
   - creating threads
   - updating posts
   - moving threads to archive forums
   - reading XenForo users
   - synchronizing XenForo user fields and groups
   - reading upgrade stats via the Upgrade Stats add-on
   - reading user-group banner data via the User Groups add-on
   - receiving and validating inbound XenForo webhooks for forum post notifications
- **Security**: Never commit to version control
- **See**: [XENFORO_OAUTH.md](XENFORO_OAUTH.md)

#### XENFORO_API_USER
- **Purpose**: Numeric XenForo user ID used as the acting API user
- **Required**: Yes for full XenForo integration
- **Common Value**: `1`
- **When to change**: Set this to the XenForo user account that should act as the API context for thread, post, and user operations

#### XENFORO_AUTHORIZE_PATH
- **Purpose**: Override XenForo OAuth authorize endpoint path
- **Required**: No
- **Default**: `/index.php?oauth2/authorize`
- **When to change**: Only if your XenForo OAuth endpoint differs from the default

#### XENFORO_TOKEN_PATH
- **Purpose**: Override XenForo OAuth token endpoint path
- **Required**: No
- **Default**: `/index.php?api/oauth2/token`
- **When to change**: Only if your XenForo OAuth endpoint differs from the default

#### XENFORO_ME_PATH
- **Purpose**: Override XenForo OAuth user-info endpoint path
- **Required**: No
- **Default**: `/api/me`
- **When to change**: Only if your XenForo OAuth endpoint differs from the default

#### XENFORO_WEBHOOK_SECRET
- **Purpose**: Validates incoming webhook requests sent by XenForo (received as the `xf-webhook-secret` request header)
- **Required**: Yes, to receive forum post notifications in Troop Tracker
- **Security**: Use a long random string; treat like a password — never commit to version control
- **Setup**: Set this value in `.env`, then enter the same string as the **Secret** when creating the webhook in XenForo Admin CP → Setup → Webhooks
- **See**: [XENFORO_OAUTH.md](XENFORO_OAUTH.md) → "Forum Post Notifications (Webhook)"

#### TRACKER_REQUIRE_XENFORO
- **Purpose**: Force Troop Tracker to require XenForo-linked accounts
- **Required**: No
- **Default**: `false`
- **When enabled**:
   - email/password login is disabled
   - non-XenForo OAuth providers are blocked
   - users without a linked XenForo account are redirected to complete XenForo linking
- **Recommended**: `true` when XenForo is your primary identity and community system
- **See**: [AUTHENTICATION.md](AUTHENTICATION.md) and [XENFORO_OAUTH.md](XENFORO_OAUTH.md)

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

## Firebase (Push Notifications)

Firebase is **optional**. When not configured, in-app notifications (the bell icon) still record and display, but mobile push delivery via FCM is silently skipped.

### FIREBASE_CREDENTIALS
- **Purpose**: Path to the Firebase Admin SDK service account JSON key file
- **Required**: No — omit to disable mobile push notifications
- **Default**: None
- **Example**: `FIREBASE_CREDENTIALS=your-firebase-adminsdk-key.json`
- **Setup**:
  1. Go to [Firebase Console](https://console.firebase.google.com/) and open your project
  2. Navigate to **Project Settings > Service Accounts**
  3. Click **Generate new private key** and download the JSON file
  4. Place the file inside `tracker-app/` (same level as `.env`)
  5. Set `FIREBASE_CREDENTIALS` to the filename
- **Security**: Never commit the JSON key to version control

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
APP_TIMEZONE=America/New_York
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
XENFORO_API_KEY=YOUR_XENFORO_API_KEY
XENFORO_API_USER=1
XENFORO_WEBHOOK_SECRET=YOUR_WEBHOOK_SECRET_HERE
TRACKER_REQUIRE_XENFORO=true
TRACKER_EXCEPTION_EMAIL_ENABLED=true

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
