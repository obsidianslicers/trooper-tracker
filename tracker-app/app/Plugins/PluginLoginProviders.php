<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Auth\GoogleLoginProvider;
use App\Contracts\LoginProvider;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * The ways troopers can sign in through another site: core's Google login
 * plus any that enabled plugins have added.
 */
final class PluginLoginProviders
{
    /**
     * @var array<int, class-string<LoginProvider>>
     */
    private array $provider_classes = [GoogleLoginProvider::class];

    public function __construct(private readonly Application $app) {}

    /**
     * @param  class-string<LoginProvider>  $provider_class
     */
    public function add(string $provider_class): void
    {
        if (!is_subclass_of($provider_class, LoginProvider::class))
        {
            throw new InvalidArgumentException(
                "[{$provider_class}] must implement ".LoginProvider::class.'.'
            );
        }

        $this->provider_classes[] = $provider_class;
    }

    /**
     * Every provider, configured or not, in the order added.
     *
     * @return array<int, LoginProvider>
     */
    public function all(): array
    {
        $providers = [];

        foreach ($this->provider_classes as $provider_class)
        {
            $provider = $this->app->make($provider_class);
            $providers[$provider->key()] = $provider;
        }

        return array_values($providers);
    }

    public function find(string $key): ?LoginProvider
    {
        foreach ($this->all() as $provider)
        {
            if ($provider->key() === $key)
            {
                return $provider;
            }
        }

        return null;
    }

    /**
     * The configured provider troopers must sign in with, if any.
     */
    public function required(): ?LoginProvider
    {
        foreach ($this->all() as $provider)
        {
            if ($provider->isRequired() && $provider->isConfigured())
            {
                return $provider;
            }
        }

        return null;
    }

    /**
     * The providers troopers can sign in with right now. While one is
     * required, only that one.
     *
     * @return array<int, LoginProvider>
     */
    public function available(): array
    {
        $required = $this->required();

        if ($required !== null)
        {
            return [$required];
        }

        return array_values(array_filter(
            $this->all(),
            fn (LoginProvider $provider): bool => $provider->isConfigured(),
        ));
    }

    public function isAvailable(string $key): bool
    {
        foreach ($this->available() as $provider)
        {
            if ($provider->key() === $key)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Why the provider can't be used to sign in right now, or null when it can.
     */
    public function unavailableReason(string $key): ?string
    {
        $required = $this->required();

        if ($required !== null && $required->key() !== $key)
        {
            return "Troop Tracker is configured to use {$required->name()} for login.";
        }

        $provider = $this->find($key);

        if ($provider === null || !$provider->isConfigured())
        {
            return ($provider?->name() ?? 'That').' login is not available.';
        }

        return null;
    }
}
