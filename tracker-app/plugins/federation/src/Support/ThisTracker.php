<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Support;

use App\Plugins\PluginManager;

/**
 * This tracker as other trackers see it: its address, name and settings.
 */
class ThisTracker
{
    public const SLUG = 'federation';

    /** Where another tracker sends a trooper back after they allow a link. */
    public const CALLBACK_PATH = '/plugins/federation/callback';

    /** Where a trooper is sent to allow a link. */
    public const AUTHORIZE_PATH = '/plugins/federation/authorize';

    public const TOKEN_PATH = '/webhooks/federation/token';

    public const HISTORY_PATH = '/webhooks/federation/history';

    public const REVOKE_PATH = '/webhooks/federation/revoke';

    public function __construct(private readonly PluginManager $plugins) {}

    public function url(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function name(): string
    {
        $name = trim((string) $this->plugins->setting(self::SLUG, 'tracker_name'));

        return $name !== '' ? $name : (string) config('app.name');
    }

    public function sharesHistory(): bool
    {
        return (bool) $this->plugins->setting(self::SLUG, 'share_history', true);
    }

    public function directoryUrl(): ?string
    {
        $url = trim((string) $this->plugins->setting(self::SLUG, 'directory_url'));

        if ($url === '')
        {
            $url = trim((string) config('plugins.federation.directory_url'));
        }

        return $url !== '' ? $url : null;
    }

    public static function callbackUrlFor(string $tracker_url): string
    {
        return $tracker_url.self::CALLBACK_PATH;
    }
}
