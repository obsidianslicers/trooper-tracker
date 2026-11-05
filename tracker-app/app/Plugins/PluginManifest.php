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
     * @param  array<string, string>  $requires  PHP, extensions, packages and the plugin
     *                                           API ("trooptracker"), each with a constraint
     * @param  array<string, string>  $plugin_requires  other plugins' slugs => version constraint
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
        public array $requires = [],
        public array $plugin_requires = [],
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
        $requires = self::requires($data, $slug);

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
            requires: $requires['packages'],
            plugin_requires: $requires['plugins'],
        );
    }

    /**
     * Read "requires": {"php": "^8.2", "ext-intl": "*", "trooptracker": "^1.0",
     * "plugins": {"other-slug": "^1.0"}}.
     *
     * @return array{packages: array<string, string>, plugins: array<string, string>}
     */
    private static function requires(array $data, string $slug): array
    {
        $requires = $data['requires'] ?? [];
        $plugins = is_array($requires) ? ($requires['plugins'] ?? []) : null;

        if (!is_array($requires) || !is_array($plugins))
        {
            throw new InvalidArgumentException(
                "Plugin [{$slug}] has a \"requires\" that is not an object."
            );
        }

        unset($requires['plugins']);

        return [
            'packages' => self::constraints($requires, $slug),
            'plugins' => self::constraints($plugins, $slug),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function constraints(array $list, string $slug): array
    {
        foreach ($list as $name => $constraint)
        {
            if (!is_string($name) || !is_string($constraint))
            {
                throw new InvalidArgumentException(
                    "Plugin [{$slug}] lists a requirement that is not \"name\": \"version\"."
                );
            }
        }

        return $list;
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
