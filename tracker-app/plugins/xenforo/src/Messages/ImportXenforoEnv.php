<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Messages;

use App\Plugins\PluginManager;
use Hyperdrive\Message;
use TroopTracker\Plugins\Xenforo\XenforoServiceProvider;

/**
 * Copies the XENFORO_* and TRACKER_REQUIRE_XENFORO .env values onto the
 * plugin's settings page. Settings already saved are kept unless overwritten.
 *
 * @method static array<int, string> call(bool $overwrite = false)
 */
final class ImportXenforoEnv extends Message
{
    private const SLUG = 'xenforo';

    public function __construct(private readonly bool $overwrite = false) {}

    /**
     * @return array<int, string> The setting keys that were copied.
     */
    public function handle(PluginManager $plugins): array
    {
        $saved = $plugins->settings(self::SLUG);
        $values = $saved;
        $copied = [];

        foreach (XenforoServiceProvider::SETTING_CONFIG as $key => $config_key)
        {
            $env_value = $this->envValue($config_key);
            $is_saved = !$this->isEmpty($saved[$key] ?? null);

            if ($env_value === null || ($is_saved && !$this->overwrite))
            {
                continue;
            }

            $values[$key] = $env_value;
            $copied[] = $key;
        }

        if ($copied !== [])
        {
            $plugins->updateSettings(self::SLUG, $values);
        }

        return $copied;
    }

    private function envValue(string $config_key): mixed
    {
        $value = config($config_key);

        return $this->isEmpty($value) ? null : $value;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === false;
    }
}
