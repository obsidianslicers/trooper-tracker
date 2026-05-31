# FAQ

## Overview

FAQ content is stored in the `tt_faq` table and managed by Command Staff via the admin panel at `/admin/faq`. Sections are stored in the `tt_faq_sections` table and managed at `/admin/faq/sections`. The public page at `/faq` renders all items dynamically from the database, grouped by section in `sort_order`.

---

## Schema

### tt_faq

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Primary key |
| `section_id` | bigint unsigned | FK → `tt_faq_sections.id`, cascadeOnDelete |
| `title` | text | Question text or video title |
| `description` | text, nullable | Markdown answer; rendered via `Str::markdown()` |
| `video_url` | string(512), nullable | YouTube URL; auto-converted to embed on display |
| `sort_order` | unsignedInteger | Ascending sort within section; default 0 |
| Standard timestamps | | `created_at`, `updated_at`, `deleted_at`, trooper stamps |

**Source:** `app/Models/Faq.php`, `app/Models/Base/Faq.php`

### tt_faq_sections

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Primary key |
| `label` | text | Display name shown on the public page |
| `icon` | string(64) | FontAwesome class (e.g. `fa-user-plus`) |
| `sort_order` | unsignedInteger | Display order on the public page; default 0 |
| Standard timestamps | | `created_at`, `updated_at`, `deleted_at`, trooper stamps |

**Source:** `app/Models/FaqSection.php`

---

## Sections

Sections are database rows in `tt_faq_sections`, not a PHP enum. The display order on the public page follows each section's `sort_order`. Sections are managed via the admin panel at `/admin/faq/sections`.

The seeded sections (in sort order) are:

| sort_order | Label | Icon |
|---|---|---|
| 1 | Getting Started & Registration | `fa-user-plus` |
| 2 | Account Types | `fa-id-card` |
| 3 | Organizations & Club Memberships | `fa-sitemap` |
| 4 | Costumes | `fa-shirt` |
| 5 | Events | `fa-calendar` |
| 6 | Signing Up for Events | `fa-clipboard-check` |
| 7 | Guests | `fa-user-group` |
| 8 | Friends | `fa-handshake` |
| 9 | How-To Videos | `fa-circle-play` |

---

## Item Types

A single `tt_faq` row serves as either a **Q&A item** or a **Video item** based on the presence of `video_url`:

| `video_url` | `description` | Rendered as |
|---|---|---|
| null | present | Accordion card with markdown answer |
| present | optional | Accordion card with 16:9 iframe embed (before description) |

All sections render as accordion cards. When `video_url` is set, a YouTube embed iframe is inserted inside the accordion item above any description text.

**Source:** `app/Models/Faq.php::embedUrl()`, `resources/views/pages/faq.blade.php`

---

## Admin CRUD

Both route groups are protected by `auth` and `check.role:administrator` middleware.

### FAQ Items

| Method | Path | Controller | Name |
|---|---|---|---|
| GET | `/admin/faq` | `ListController` | `admin.faq.list` |
| GET | `/admin/faq/create` | `CreateController` | `admin.faq.create` |
| POST | `/admin/faq/create` | `CreateSubmitController` | — |
| GET | `/admin/faq/{faq}/update` | `UpdateController` | `admin.faq.update` |
| POST | `/admin/faq/{faq}/update` | `UpdateSubmitController` | — |
| POST | `/admin/faq/{faq}/delete` | `DeleteSubmitController` | `admin.faq.delete` |

**Source:** `routes/web/admin-faq.php`, `app/Http/Controllers/Admin/Faq/`

### FAQ Sections

| Method | Path | Controller | Name |
|---|---|---|---|
| GET | `/admin/faq/sections` | `SectionListController` | `admin.faq.sections.list` |
| GET | `/admin/faq/sections/create` | `SectionCreateController` | `admin.faq.sections.create` |
| POST | `/admin/faq/sections/create` | `SectionCreateSubmitController` | — |
| GET | `/admin/faq/sections/{section}/update` | `SectionUpdateController` | `admin.faq.sections.update` |
| POST | `/admin/faq/sections/{section}/update` | `SectionUpdateSubmitController` | — |
| POST | `/admin/faq/sections/{section}/delete` | `SectionDeleteSubmitController` | `admin.faq.sections.delete` |

**Source:** `routes/web/admin-faq.php`, `app/Http/Controllers/Admin/FaqSections/`

---

## Seeding

The initial content from the static FAQ page is available as a one-time seeder:

```
php artisan db:seed --class=FaqSeeder
```

**Source:** `database/seeders/FaqSeeder.php`
