# Localization

The tracker can show its interface in more than one language. Each garrison sets a default language for its site, and each trooper can pick their own on the account page. Text that garrisons write themselves (events, FAQs, notices, costume and award names) is not translated.

## Choosing the language

| Setting | Where | Notes |
|---|---|---|
| Languages offered | `config/tracker.php` → `locales` | `locale => the language's own name`, e.g. `'de' => 'Deutsch'`. The account page shows a Language dropdown only when more than one is listed. |
| Site default | `APP_LOCALE` in `.env` | Must be one of the offered languages; anything else falls back to English. |
| Fallback | `APP_FALLBACK_LOCALE=en` | Leave this as `en`. |
| Per trooper | `tt_troopers.locale` | Null means the site default. A language the site no longer offers is ignored. |

`SetLocaleMiddleware` applies the trooper's language (or the site default) on every web request. `Trooper` implements `HasLocalePreference`, so queued mail and notifications sent to a trooper (`$trooper->notify(...)`, `Mail::to($trooper)`) render in that trooper's language. Mail sent to a bare email address uses the site default.

Carbon follows the app locale, so `diffForHumans()` and `isoFormat()` come out in the right language.

## Translation files

Strings live in one PHP file per feature and language, keyed by the English text:

```
lang/
├── en/
│   ├── common.php      # shared by every page (also sent to Svelte and JS)
│   ├── enums.php       # enum labels
│   └── <feature>.php   # events.php, troopers.php, admin.php, emails.php, ...
└── de/
    └── (same files, any subset of keys)
```

```php
// lang/de/common.php
return [
    'Language' => 'Sprache',
    'Welcome back, :name' => 'Willkommen zurück, :name',
];
```

Rules:

1. **Every key goes in the `en` file**, even though its value repeats the key. Laravel falls back to `en`; if the key is missing there too, the page shows the raw `common.Welcome back, :name`. `TranslationFilesTest` fails when code uses a key English doesn't define.
2. **Periods in keys are fine** (`'Saved. Thanks!'`) as long as the key exists, because Laravel tries the whole key before splitting on dots.
3. **Use `:placeholders`**, never sentences glued together from pieces: word order changes between languages. Plurals use Laravel's form: `'{0} No troops|{1} One troop|[2,*] :count troops'` with `trans_choice()`.
4. **Rewording English changes the key.** Translations of the old wording become orphaned; `TranslationFilesTest` fails until they are re-keyed or removed.
5. **Keep keys literal.** `__('events.Sign up')` can be checked; `__("events.{$label}")` can't. Enum labels are the one built-in exception and have their own test.

## Using translations

**Blade and PHP**

```blade
<h1>{{ __('troopers.Trooper Profile') }}</h1>
<p>{{ __('common.Welcome back, :name', ['name' => $trooper->display_name]) }}</p>
```

**Svelte.** Every Inertia page gets the `common` group as the shared `i18n` prop. A page that needs more sends its groups as a `lang` prop from its PageData:

```php
'lang' => app(TranslationService::class)->groups('troopers'),
```

```svelte
<script lang="ts">
    import { t } from '$lib/i18n';
</script>

<h1>{t('Trooper Profile')}</h1>
<p>{t('Welcome back, :name', { name: trooper.display_name })}</p>
<p>{t(':count troop|:count troops', {}, troops.length)}</p>
```

`t()` looks in the page's `lang` first, then `common`, and falls back to the English key. `TranslationService::groups()` lays each group over its English version, because `trans('troopers')` on its own returns only the current language's lines. `locale()` from `$lib/i18n` gives the language tag for `Intl.DateTimeFormat`.

**Plain JS on Blade pages.** The base layout embeds the `common` group in `#tt-i18n`; import `t` from `resources/js/i18n.js`.

**Enums.** `HasEnumHelpers::toArray()`, `toValueLabels()` and `caseLabel()` translate labels through `lang/<locale>/enums.php`. Add a new case's label to `lang/en/enums.php`; `TranslationFilesTest` checks every enum.

## Dates and numbers

- **Dates:** use `$date->localFormat('M j, Y')` instead of `->format()` for anything people read. The pattern is the key into `lang/<locale>/formats.php`, so English stays as written and another language maps it to its own order (`'M j, Y' => 'j. M Y'`, `'g:ia' => 'H:i'`). Carbon translates the month and day names. Add new patterns to `lang/en/formats.php`; `TranslationFilesTest` checks them. Keep `->format()` for machine values (`Y-m-d`, form inputs, CSV, URLs).
- **Numbers:** `format_number($value, $decimals)` gives `1,234.5` in English and `1.234,5` in German. `format_funds()` follows the language too, unless it's given another thousands separator (form inputs pass `''`).
- **Svelte:** `formatDate()` and `formatDateTime()` in `$lib/utils` use `locale()` from `$lib/i18n`.
- Currency symbols are still hardcoded as `$`.

## Emails and notifications

- **Subjects and notification text** use the `emails` and `notifications` groups like any other string: `__('emails.Account Deletion Requested')`, `__('notifications.:trooper has requested to join :organization.', [...])`. Use `trans_choice` for counts.
- **Email bodies** are whole Blade views per language under `resources/views/emails/<locale>/`, because long letters read better translated as a whole than line by line. English lives in `emails/en/`. Mailables pick the view with `email_view('events.trooper-signup')`, which returns `emails.<locale>.<name>` when that file exists and falls back to `emails.en.<name>`. Includes inside an email use it too: `@include(email_view('inc.signature'))`.
- To translate an email, copy its view from `emails/en/` to `emails/<locale>/` at the same path and translate it. Emails you haven't copied stay in English.
- Mail and notifications render in the recipient's language (see above), so `email_view()`, `__()` and `localFormat()` all follow the trooper.
- System mail for maintainers (exception reports, Supervisor alerts, one-off `Fix…` reports) stays in English.

## Plugins

A plugin ships `lang/<locale>/<group>.php` in its own folder. The plugin loader registers it under the plugin's slug:

```php
__('discord::settings.Webhook URL')
```

```php
'lang' => app(TranslationService::class)->groups('discord::settings'),
```

The same rules apply: English text as keys and a complete `en` folder. Core never edits a plugin's lang files.

## Checking translations

| Check | What it catches | Fails the build? |
|---|---|---|
| `TranslationFilesTest` (PHPUnit) | Keys used in code but missing from `en`; keys another language has that `en` dropped; enum labels missing from `en/enums.php` | Yes |
| `php artisan tracker:report-translations` | Per language: lines translated, missing (falls back to English) and orphaned | No; CI writes it to the job summary |

Locally (`APP_ENV=local`), a key that no language defines is also logged as a warning.

## Adding a language

1. Copy `lang/en` to `lang/<locale>` and translate the values (leave out lines you haven't translated yet; they fall back to English).
2. Add the language to `locales` in `config/tracker.php`.
3. Validation messages: run `php artisan lang:publish` once to get `lang/en/validation.php`, then add `lang/<locale>/validation.php`. The [laravel-lang](https://github.com/Laravel-Lang/lang) project has ready-made translations of it for most languages.
4. Run `php artisan tracker:report-translations` to see what's left.
