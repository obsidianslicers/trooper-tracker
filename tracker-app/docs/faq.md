# FAQ

## Overview

FAQ content is stored in the `tt_faq` table and managed by Command Staff via the admin panel at `/admin/faq`. The public page at `/faq` renders all items dynamically from the database.

---

## Schema

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Primary key |
| `section` | string(64) | `FaqSection` enum value |
| `title` | text | Question text or video title |
| `description` | text, nullable | Markdown answer; rendered via `Str::markdown()` |
| `video_url` | string(512), nullable | YouTube URL; auto-converted to embed on display |
| `sort_order` | unsignedInteger | Ascending sort within section; default 0 |
| Standard timestamps | | `created_at`, `updated_at`, `deleted_at`, trooper stamps |

**Table:** `tt_faq`  
**Source:** `app/Models/Faq.php`, `app/Models/Base/Faq.php`

---

## Sections

Sections are a fixed enum — no separate table. The display order on the public page follows the enum case order.

| Enum case | DB value | Label |
|---|---|---|
| `REGISTRATION` | `registration` | Getting Started & Registration |
| `ACCOUNT_TYPES` | `account-types` | Account Types |
| `ORGANIZATIONS` | `organizations` | Organizations & Club Memberships |
| `COSTUMES` | `costumes` | Costumes |
| `EVENTS` | `events` | Events |
| `SIGNUP` | `signup` | Signing Up for Events |
| `GUESTS` | `guests` | Guests |
| `FRIENDS` | `friends` | Friends |
| `VIDEOS` | `videos` | How-To Videos |

**Source:** `app/Enums/FaqSection.php`

---

## Item Types

A single `tt_faq` row serves as either a **Q&A item** or a **Video item** based on the presence of `video_url`:

| `video_url` | `description` | Rendered as |
|---|---|---|
| null | present | Accordion card with markdown answer |
| present | optional | YouTube embed iframe |

The `Videos` section always renders as a grid of embed cards or "coming soon" placeholders. All other sections render as accordion cards.

**Source:** `app/Models/Faq.php::embedUrl()`, `resources/views/pages/faq.blade.php`

---

## Admin CRUD

Routes are protected by `check.role:administrator` middleware.

| Method | Path | Controller | Name |
|---|---|---|---|
| GET | `/admin/faq` | `ListController` | `admin.faq.list` |
| GET | `/admin/faq/create` | `CreateController` | `admin.faq.create` |
| POST | `/admin/faq/create` | `CreateSubmitController` | — |
| GET | `/admin/faq/{faq}/update` | `UpdateController` | `admin.faq.update` |
| POST | `/admin/faq/{faq}/update` | `UpdateSubmitController` | — |
| POST | `/admin/faq/{faq}/delete` | `DeleteSubmitController` | `admin.faq.delete` |

**Source:** `routes/web/admin-faq.php`, `app/Http/Controllers/Admin/Faq/`

---

## Seeding

The initial content from the static FAQ page is available as a one-time seeder:

```
php artisan db:seed --class=FaqSeeder
```

**Source:** `database/seeders/FaqSeeder.php`
