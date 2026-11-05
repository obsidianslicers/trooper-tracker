<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a plugin's files came from.
 */
enum PluginSource: string
{
    use HasEnumHelpers;

    /** In tracker-app/plugins, from the repository or copied onto the server. */
    case BUNDLED = 'bundled';

    /** Copied by hand into the persistent plugins folder (storage/app/plugins). */
    case CUSTOM = 'custom';

    /** Uploaded as a zip on the Plugins page. */
    case UPLOADED = 'uploaded';

    /** Installed by Composer. */
    case COMPOSER = 'composer';
}
