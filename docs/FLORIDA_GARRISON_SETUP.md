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

## 3. Create the Public Image Link

Troop Tracker stores new uploads on Laravel's `public` storage disk. The app needs the standard Laravel storage symlink so browsers can load uploaded files.

Run:

```bash
php artisan storage:link
```

This creates the public link for files stored under:

- `storage/app/public/...`

Without this step, new organization logos and new event uploads will save, but they will not render correctly in the browser.

---

## 4. Understand the Two Image Paths

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

## 5. Transfer Old Uploaded Event Images

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

## 6. Upload New Event Photos in the New Tracker

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

## 7. Upload Squad Photos and Organization Logos

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

## 8. Bulk Migration Option for Squad Photos

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

## 9. Verification Checklist

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

## 10. Suggested Order for Florida Garrison Launch

Use this order when standing up the Florida Garrison instance:

1. configure `.env`
2. run migrations
3. seed with `FloridaGarrisonSeeder`
4. run `php artisan storage:link`
5. migrate legacy event images
6. upload or migrate squad logos
7. verify several events and organization pages in the browser
