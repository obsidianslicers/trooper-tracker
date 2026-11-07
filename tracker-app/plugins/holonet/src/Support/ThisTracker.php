<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

use App\Plugins\PluginManager;

/**
 * This tracker as other trackers see it: its address, name and settings.
 */
class ThisTracker
{
    public const SLUG = 'holonet';

    /** Where another tracker sends a trooper back after they allow a link. */
    public const CALLBACK_PATH = '/plugins/holonet/callback';

    /** Where a trooper is sent to allow a link. */
    public const AUTHORIZE_PATH = '/plugins/holonet/authorize';

    /** Where another tracker checks HoloNet is running here. */
    public const INSTANCE_PATH = '/plugins/holonet/instance';

    public const TOKEN_PATH = '/webhooks/holonet/token';

    public const HISTORY_PATH = '/webhooks/holonet/history';

    public const REVOKE_PATH = '/webhooks/holonet/revoke';

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
            $url = trim((string) config('plugins.holonet.directory_url'));
        }

        return $url !== '' ? $url : null;
    }

    public static function callbackUrlFor(string $tracker_url): string
    {
        return $tracker_url.self::CALLBACK_PATH;
    }
}
