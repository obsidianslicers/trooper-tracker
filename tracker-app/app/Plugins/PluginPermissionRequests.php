<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Contracts\PermissionRequestExtension;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * The permission request extensions enabled plugins have added.
 */
final class PluginPermissionRequests
{
    /**
     * @var array<int, class-string<PermissionRequestExtension>>
     */
    private array $extension_classes = [];

    public function __construct(private readonly Application $app) {}

    /**
     * @param  class-string<PermissionRequestExtension>  $extension_class
     */
    public function add(string $extension_class): void
    {
        if (!is_subclass_of($extension_class, PermissionRequestExtension::class))
        {
            throw new InvalidArgumentException(
                "[{$extension_class}] must implement ".PermissionRequestExtension::class.'.'
            );
        }

        $this->extension_classes[] = $extension_class;
    }

    /**
     * @return array<string, PermissionRequestExtension> Keyed by extension key.
     */
    public function all(): array
    {
        $extensions = [];

        foreach ($this->extension_classes as $extension_class)
        {
            $extension = $this->app->make($extension_class);
            $extensions[$extension->key()] = $extension;
        }

        return $extensions;
    }
}
