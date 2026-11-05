<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Contracts\ForumGroupProvider;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * The forum group provider an enabled plugin has set, if any.
 */
final class PluginForumGroups
{
    /**
     * @var class-string<ForumGroupProvider>|null
     */
    private ?string $provider_class = null;

    public function __construct(private readonly Application $app) {}

    /**
     * @param  class-string<ForumGroupProvider>  $provider_class
     */
    public function set(string $provider_class): void
    {
        if (!is_subclass_of($provider_class, ForumGroupProvider::class))
        {
            throw new InvalidArgumentException(
                "[{$provider_class}] must implement ".ForumGroupProvider::class.'.'
            );
        }

        $this->provider_class = $provider_class;
    }

    /**
     * The provider, only while it is available.
     */
    public function provider(): ?ForumGroupProvider
    {
        if ($this->provider_class === null)
        {
            return null;
        }

        $provider = $this->app->make($this->provider_class);

        return $provider->isAvailable() ? $provider : null;
    }
}
