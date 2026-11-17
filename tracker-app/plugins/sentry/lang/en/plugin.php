<?php

// Keys are the English text: plugin.json's description and setting labels and help,
// plus labels the plugin registers. See docs/LOCALIZATION.md.

return [
    'Ad blockers often block sentry.io. With this on, browsers send errors to /webhooks/sentry, which passes them on to the Browser DSN\'s project and nowhere else.' => 'Ad blockers often block sentry.io. With this on, browsers send errors to /webhooks/sentry, which passes them on to the Browser DSN\'s project and nowhere else.',
    'Browser DSN' => 'Browser DSN',
    'Environment' => 'Environment',
    'Errors in members\' browsers go to this Sentry project (it can be the same one). It is visible in each page. Leave empty to use SENTRY_BROWSER_DSN from the .env file; with neither, browser errors aren\'t sent.' => 'Errors in members\' browsers go to this Sentry project (it can be the same one). It is visible in each page. Leave empty to use SENTRY_BROWSER_DSN from the .env file; with neither, browser errors aren\'t sent.',
    'Errors on the server go to this Sentry project. Leave empty to use SENTRY_DSN from the .env file.' => 'Errors on the server go to this Sentry project. Leave empty to use SENTRY_DSN from the .env file.',
    'Send browser errors through this site' => 'Send browser errors through this site',
    'Sends errors from the server and from members\' browsers to Sentry (sentry.io), so they can be fixed before anyone reports them.' => 'Sends errors from the server and from members\' browsers to Sentry (sentry.io), so they can be fixed before anyone reports them.',
    'Sentry server DSN' => 'Sentry server DSN',
    'Server DSN' => 'Server DSN',
    'Shown on each error in Sentry, such as production or staging. Leave empty to use APP_ENV.' => 'Shown on each error in Sentry, such as production or staging. Leave empty to use APP_ENV.',
];
