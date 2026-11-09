<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Enums\PluginCapability;
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
     * @param  array<int, PluginCapability>  $capabilities
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
        public array $settings = [],
        public array $requires = [],
        public array $plugin_requires = [],
        public array $capabilities = [],
    ) {}

    /**
     * The Composer packages this plugin needs, from its composer.json and
     * plugin.json. The deploy installs them.
     *
     * @return array<string, string>
     */
    public function packages(): array
    {
        $require = $this->composerJson()['require'] ?? [];

        return PluginRequirements::split([
            ...(is_array($require) ? $require : []),
            ...$this->requires,
        ])['packages'];
    }

    /**
     * The plugin's composer.json, or nothing if it has none.
     *
     * @return array<string, mixed>
     */
    public function composerJson(): array
    {
        $file = $this->path.'/composer.json';

        if (!is_file($file))
        {
            return [];
        }

        $composer = json_decode((string) file_get_contents($file), true);

        return is_array($composer) ? $composer : [];
    }

    public function can(PluginCapability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    /**
     * Build a manifest from decoded plugin.json data.
     *
     * A plugin must declare a "namespace" so the plugin loader can autoload its
     * "src" directory.
     */
    public static function fromArray(array $data, string $path): self
    {
        $slug = self::slug($data, $path);

        if (empty($data['provider']) || !is_string($data['provider']))
        {
            throw new InvalidArgumentException("Plugin [{$slug}] needs a \"provider\" class.");
        }

        $namespace = self::namespace($data, $slug);
        $requires = self::requires($data, $slug);

        $normalized_path = rtrim($path, '/\\');

        return new self(
            slug: $slug,
            name: (string) ($data['name'] ?? $slug),
            version: (string) ($data['version'] ?? '0.0.0'),
            description: (string) ($data['description'] ?? ''),
            author: (string) ($data['author'] ?? ''),
            provider: ltrim($data['provider'], '\\'),
            path: $normalized_path,
            namespace: $namespace,
            source_path: $namespace === null ? null : $normalized_path.'/src',
            settings: array_map(
                fn (array $setting): PluginSetting => PluginSetting::fromArray($setting),
                array_values($data['settings'] ?? []),
            ),
            requires: $requires['packages'],
            plugin_requires: $requires['plugins'],
            capabilities: self::capabilities($data, $slug),
        );
    }

    /**
     * @return array<int, PluginCapability>
     */
    private static function capabilities(array $data, string $slug): array
    {
        $values = $data['capabilities'] ?? [];

        if (!is_array($values))
        {
            throw new InvalidArgumentException(
                "Plugin [{$slug}] has \"capabilities\" that is not a list."
            );
        }

        return array_values(array_map(
            fn (mixed $value): PluginCapability => PluginCapability::tryFrom((string) $value)
                ?? throw new InvalidArgumentException(
                    "Plugin [{$slug}] lists an unknown capability [{$value}]."
                ),
            $values,
        ));
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

    private static function namespace(array $data, string $slug): ?string
    {
        $namespace = isset($data['namespace'])
            ? trim((string) $data['namespace'], '\\').'\\'
            : null;

        if ($namespace === null)
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
