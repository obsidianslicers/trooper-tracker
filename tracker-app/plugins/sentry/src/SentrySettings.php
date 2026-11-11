<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Sentry;

use App\Plugins\PluginManager;

/**
 * The plugin's settings, each falling back to its .env value when nothing is saved.
 */
final class SentrySettings
{
    public function __construct(private readonly PluginManager $plugins) {}

    public function serverDsn(): ?string
    {
        return $this->value('dsn');
    }

    public function browserDsn(): ?string
    {
        return $this->value('browser_dsn');
    }

    public function environment(): string
    {
        return $this->value('environment') ?? (string) app()->environment();
    }

    public function tunnel(): bool
    {
        return (bool) $this->plugins->setting('sentry', 'tunnel', true);
    }

    private function value(string $key): ?string
    {
        $value = $this->plugins->setting('sentry', $key);

        if (blank($value))
        {
            $value = config("plugins.sentry.{$key}");
        }

        return blank($value) ? null : trim((string) $value);
    }
}
