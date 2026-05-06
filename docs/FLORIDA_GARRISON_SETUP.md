# Florida Garrison Setup Guide

This guide covers the Florida Garrison-specific setup steps for a new Troop Tracker instance, including:

- seeding the Florida Garrison organization structure
- creating the public image link
- transferring legacy uploaded event images into the new tracker
- uploading squad photos and organization logos

Use this document when standing up a fresh Florida Garrison environment or when migrating from the older tracker.

---

## 1. Prerequisites

Run all Laravel commands from `tracker-app/`.

Minimum setup before Florida-specific work:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Recommended `.env` values for Florida Garrison:

- `APP_NAME="501st Florida Garrison"`
- `TRACKER_CALENDAR_TIMEZONE=America/New_York`
- `APP_URL` set to the real deployment URL
- `APP_DEBUG=false` in production
- `GOOGLE_MAPS_API_KEY` set to your Google Maps API key (required for the map feature — see section 3)

If this is a production or shared environment, make sure the database, queue, mail, and OAuth settings are configured before launch.

---

## 2. Seed Florida Garrison Data

For a fresh Florida Garrison setup, seed the dedicated dataset:

```bash
php artisan db:seed --class=FloridaGarrisonSeeder
```

This seeds the core club structure and then adds Florida-specific regions and units such as:

- Florida Garrison
- Everglades Squad
- Makaze Squad
- Tampa Bay Squad
- Squad 7
- Parjai Squad

The seeder also runs `tracker:synchronize-organizations` at the end.

If you prefer using the composer shortcut already defined in the app:

```bash
composer db:migrate-all
```

Use that only when you are intentionally rebuilding the database.

---

## 3. Configure Google Maps API Key

The map feature on event pages uses the Google Maps JavaScript API. Without a valid API key the map will not render.

### 3.1 Create or reuse a Google Cloud project

1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Select an existing project or create a new one (e.g. `troop-tracker`).

### 3.2 Enable the required APIs

In the Google Cloud Console for your project, enable **all** of the following APIs:

- **Maps JavaScript API** — renders the interactive map on event pages
- **Geocoding API** — converts a street address to latitude/longitude coordinates

To enable each API:

1. Open **APIs & Services > Library**.
2. Search for the API name.
3. Click **Enable**.

### 3.3 Create an API key

1. Open **APIs & Services > Credentials**.
2. Click **+ Create Credentials > API key**.
3. Copy the generated key.

### 3.4 Restrict the API key (recommended for production)

In the API key settings:

- Under **Application restrictions**, select **HTTP referrers (websites)**.
- Add your domain, for example: `https://trooptracker.fl501st.com/*`
- Under **API restrictions**, select **Restrict key** and choose only the APIs enabled above.

### 3.5 Add the key to `.env`

```env
GOOGLE_MAPS_API_KEY=your_api_key_here
```

After saving `.env`, clear the config cache if you are in production:

```bash
php artisan config:clear
```

The map will now render on event detail pages for events that have a valid address.

---

## 4. Create the Public Image Link

Troop Tracker stores new uploads on Laravel's `public` storage disk. The app needs the standard Laravel storage symlink so browsers can load uploaded files.

Run:

```bash
php artisan storage:link
```

This creates the public link for files stored under:

- `storage/app/public/...`

Without this step, new organization logos and new event uploads will save, but they will not render correctly in the browser.

---

## 5. Understand the Two Image Paths

There are two different image-handling patterns in this project.

### New Tracker Uploads

New uploads are stored on the Laravel public disk:

- organization images: `storage/app/public/uploads/organizations/...`
- event images: `storage/app/public/uploads/events/...`

### Legacy Event Upload Compatibility

Legacy event uploads can still work if the database stores only an old filename instead of a storage path.

When an `EventUpload` record contains a filename with no slash, the app serves it from:

- `public/images/uploads/<filename>`

This compatibility behavior applies to event uploads only.

Organization images do not have the same fallback behavior. Squad photos and logos should be re-uploaded through the admin UI or migrated into the new storage path format.

---

## 6. Transfer Old Uploaded Event Images

If you are migrating uploaded event photos from the old tracker, preserve the original filenames.

### Recommended Legacy Migration Path

1. Copy the old uploaded files into:

   `tracker-app/public/images/uploads/`

2. Import or migrate the event upload records so that:

   - `tt_event_uploads.image_path_lg = <legacy filename>`
   - `tt_event_uploads.image_path_sm = <legacy filename>`

3. Keep the original record IDs if you are using the existing Florida migration seeders and want database continuity.

4. Open several migrated events in the app and verify the gallery thumbnails load.

### If the Legacy `uploads` Table Still Exists

The existing Florida migration seeder already knows how to copy legacy upload metadata into `tt_event_uploads`:

```bash
php artisan db:seed --class=FloridaGarrisonSeeder
```

That seeder's event upload migration reads from the old `uploads` table and stores the legacy filename in both image-path columns.

### Important Notes

- This legacy path is for existing migrated photos.
- New event photos uploaded through Troop Tracker do not use `public/images/uploads/`.
- New event photos are stored under `storage/app/public/uploads/events/...` and require `php artisan storage:link`.

---

## 7. Upload New Event Photos in the New Tracker

Administrative event uploads are managed in the web UI.

Path in the UI:

- `Command Staff`
- `Events`
- open the event
- `Uploads`

On the uploads screen:

1. Drag images onto the upload zone, or click it to choose files.
2. The app stores the original image under `uploads/events/{event_id}/originals/`.
3. The app creates a PNG thumbnail under `uploads/events/{event_id}/thumbnails/`.

Current limits for event uploads:

- formats: PNG, JPG, JPEG, WEBP
- max file size: 4 MB per image

Trooper uploads and admin uploads use the same storage pattern.

---

## 8. Upload Squad Photos and Organization Logos

In this codebase, squad photos are handled as organization images or logos.

That means you use the same flow for:

- Florida Garrison itself
- a squad such as Tampa Bay Squad
- any other region or unit

Path in the UI:

- `Command Staff`
- `Organizations`
- open the organization or squad
- click the current image

The image panel on the organization update page lets you click the current logo to replace it.

Current limits for organization image uploads:

- formats: PNG, JPG, JPEG, WEBP
- max file size: 2 MB

When uploaded, the app automatically creates:

- large image: `uploads/organizations/{organization_id}-128x128.png`
- small image: `uploads/organizations/{organization_id}-32x32.png`

These are stored on the `public` disk under `storage/app/public/` and displayed through the storage symlink.

### Recommendation

For squad photos, prefer re-uploading each logo through the admin UI. That guarantees the image is normalized and both required sizes are generated correctly.

---

## 9. Bulk Migration Option for Squad Photos

If you must migrate squad logos in bulk instead of re-uploading them manually, match the storage pattern used by the organization image upload controller.

For each organization ID:

1. Create a 128x128 PNG file at:

   `storage/app/public/uploads/organizations/{organization_id}-128x128.png`

2. Create a 32x32 PNG file at:

   `storage/app/public/uploads/organizations/{organization_id}-32x32.png`

3. Update the database row in `tt_organizations`:

   - `image_path_lg = uploads/organizations/{organization_id}-128x128.png`
   - `image_path_sm = uploads/organizations/{organization_id}-32x32.png`

4. Confirm `php artisan storage:link` has already been run.

If the source images are inconsistent, transparent, non-square, or low quality, the admin re-upload path is safer than a bulk copy.

---

## 10. Firebase Push Notifications (Optional)

Mobile push notifications are delivered via Firebase Cloud Messaging (FCM). This step is optional — the app functions without it, and in-app notifications (the bell icon) continue to work regardless.

### 10.1 Create a Firebase project

1. Go to [Firebase Console](https://console.firebase.google.com/).
2. Click **Add project** and follow the prompts (or select an existing project).
3. You do not need to enable Google Analytics.

### 10.2 Generate a service account key

1. In the Firebase Console, open **Project Settings > Service Accounts**.
2. Click **Generate new private key**.
3. Download the JSON file — treat it like a password.

### 10.3 Add the key to the server

Place the JSON file inside `tracker-app/` (same directory as `.env`):

```bash
scp your-firebase-adminsdk-key.json bitnami@your-server:/home/bitnami/trooper-tracker/tracker-app/
```

### 10.4 Set the env variable

Add to `.env`:

```env
FIREBASE_CREDENTIALS=your-firebase-adminsdk-key.json
```

Then clear config:

```bash
php artisan config:clear
```

### 10.5 Test

Send a test push notification to a trooper (by their numeric ID):

```bash
php artisan tracker:send-test-push {trooper_id}
```

If the trooper has the mobile app installed and has granted notification permission, a push should arrive within a few seconds.

### 10.6 What happens without Firebase

- In-app notification records are still created and visible in the bell icon.
- FCM delivery is silently skipped — no errors, no crashes.
- The `saveFCM` / `logoutFCM` API endpoints still work and store tokens; they just have no effect until a key is configured.

---

## 11. Verification Checklist

After setup or migration, verify all of the following:

- the Florida Garrison region and squads exist in `Command Staff > Organizations`
- a squad logo appears in organization lists and detail pages
- a newly uploaded squad logo renders after page refresh
- legacy migrated event photos load on existing events
- newly uploaded event photos appear in the event uploads page
- broken image icons do not appear in the public UI

If new uploads are broken but legacy images work, the most likely issue is that `php artisan storage:link` was not run or the symlink is missing on the server.

If legacy event images are broken but new uploads work, the most likely issue is that the legacy files were not copied into `public/images/uploads/` with the original filenames intact.

---

## 12. Suggested Order for Florida Garrison Launch

Use this order when standing up the Florida Garrison instance:

1. configure `.env`
2. run migrations
3. seed with `FloridaGarrisonSeeder`
4. add `GOOGLE_MAPS_API_KEY` to `.env` and run `php artisan config:clear`
5. run `php artisan storage:link`
6. migrate legacy event images
7. upload or migrate squad logos
8. (optional) add `FIREBASE_CREDENTIALS` to `.env` for mobile push notifications — see section 10
9. verify several events and organization pages in the browser

---

## 13. Laravel Queue Worker and SES SMTP on Bitnami AWS

### 13.1 Set up Laravel queue worker with Supervisor

1. **Find the PHP binary**

   ```bash
   which php
   ls -l /opt/bitnami/php/bin/php
   ```

   If this exists, use `/opt/bitnami/php/bin/php`.

2. **Install Supervisor if needed**

   ```bash
   sudo apt-get update
   sudo apt-get install -y supervisor
   ```

3. **Create the Supervisor config**

   ```bash
   sudo tee /etc/supervisor/conf.d/laravel-worker.conf > /dev/null <<'EOF'
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=/opt/bitnami/php/bin/php /home/bitnami/trooper-tracker/tracker-app/artisan queue:work --sleep=3 --tries=3 --timeout=90
   directory=/home/bitnami/trooper-tracker/tracker-app
   autostart=true
   autorestart=true
   startsecs=5
   startretries=3
   user=bitnami
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/home/bitnami/laravel-worker.log
   stopwaitsecs=3600
   environment=APP_ENV="production"
   EOF
   ```

4. **Reload Supervisor and start the worker**

   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-worker:*
   ```

5. **Verify it is running**

   ```bash
   sudo supervisorctl status
   ```

   Expected result:

   ```text
   laravel-worker:laravel-worker_00   RUNNING
   ```

6. **Watch the worker log**

   ```bash
   tail -f /home/bitnami/laravel-worker.log
   ```

7. **Restart the worker after deploys or .env changes**

   ```bash
   cd /home/bitnami/trooper-tracker/tracker-app
   /opt/bitnami/php/bin/php artisan queue:restart
   ```

8. **Make sure Laravel queue tables exist**

   ```bash
   cd /home/bitnami/trooper-tracker/tracker-app
   /opt/bitnami/php/bin/php artisan queue:table
   /opt/bitnami/php/bin/php artisan queue:failed-table
   /opt/bitnami/php/bin/php artisan migrate --force
   ```

9. **Make sure .env uses a real queue driver**

   Example:

   ```env
   QUEUE_CONNECTION=database
   ```

10. **Check queued and failed jobs**

    ```bash
    cd /home/bitnami/trooper-tracker/tracker-app
    /opt/bitnami/php/bin/php artisan queue:failed
    ```

    Retry failed jobs:

    ```bash
    /opt/bitnami/php/bin/php artisan queue:retry all
    ```

    Flush failed jobs:

    ```bash
    /opt/bitnami/php/bin/php artisan queue:flush
    ```

### 13.2 Common mail setup note for SES SMTP

Use this in `.env`:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=YOUR_SES_SMTP_USERNAME
MAIL_PASSWORD=YOUR_SES_SMTP_PASSWORD
MAIL_FROM_ADDRESS=gwm@fl501st.com
MAIL_FROM_NAME="Troop Tracker"
```

Then clear config and restart the worker:

```bash
cd /home/bitnami/trooper-tracker/tracker-app
/opt/bitnami/php/bin/php artisan config:clear
/opt/bitnami/php/bin/php artisan cache:clear
/opt/bitnami/php/bin/php artisan queue:restart
```

### 13.3 If Supervisor says "can't find command 'php'"

That means you must use the full PHP path in the `command` line of the Supervisor program:

```ini
command=/opt/bitnami/php/bin/php ...
```

