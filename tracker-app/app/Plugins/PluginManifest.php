<?php

declare(strict_types=1);

namespace App\Plugins;

use InvalidArgumentException;

/**
 * The parsed contents of a plugin's plugin.json file.
 */
final readonly class PluginManifest
{
    /**
     * @param  array<int, PluginSetting>  $settings
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $version,
        public string $description,
        public string $author,
        public string $provider,
        public string $path,
        public ?string $namespace,
        public ?string $source_path,
        public bool $is_composer_package,
        public array $settings = [],
    ) {}

    /**
     * Build a manifest from decoded plugin.json data.
     *
     * Local plugins must declare a "namespace" so the plugin loader can autoload
     * their "src" directory. Composer packages are autoloaded by Composer.
     */
    public static function fromArray(array $data, string $path, bool $is_composer_package): self
    {
        $slug = self::slug($data, $path);

        if (empty($data['provider']) || !is_string($data['provider']))
        {
            throw new InvalidArgumentException("Plugin [{$slug}] needs a \"provider\" class.");
        }

        $namespace = self::namespace($data, $slug, $is_composer_package);

        return new self(
            slug: $slug,
            name: (string) ($data['name'] ?? $slug),
            version: (string) ($data['version'] ?? '0.0.0'),
            description: (string) ($data['description'] ?? ''),
            author: (string) ($data['author'] ?? ''),
            provider: ltrim($data['provider'], '\\'),
            path: rtrim($path, DIRECTORY_SEPARATOR),
            namespace: $namespace,
            source_path: $namespace === null ? null : rtrim($path, DIRECTORY_SEPARATOR).'/src',
            is_composer_package: $is_composer_package,
            settings: array_map(
                fn (array $setting): PluginSetting => PluginSetting::fromArray($setting),
                array_values($data['settings'] ?? []),
            ),
        );
    }

    private static function slug(array $data, string $path): string
    {
        $slug = (string) ($data['slug'] ?? '');

        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) !== 1)
        {
            throw new InvalidArgumentException("Plugin at [{$path}] needs a kebab-case \"slug\".");
        }

        return $slug;
    }

    private static function namespace(array $data, string $slug, bool $is_composer_package): ?string
    {
        $namespace = isset($data['namespace'])
            ? trim((string) $data['namespace'], '\\').'\\'
            : null;

        if (!$is_composer_package && $namespace === null)
        {
            throw new InvalidArgumentException(
                "Plugin [{$slug}] needs a \"namespace\" to autoload."
            );
        }

        return $namespace;
    }

    public function hasSettings(): bool
    {
        return $this->settings !== [];
    }

    public function migrationsPath(): string
    {
        return $this->path.'/database/migrations';
    }
}
