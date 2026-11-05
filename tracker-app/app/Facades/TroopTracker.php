<?php

declare(strict_types=1);

namespace App\Facades;

use App\Contracts\LoginProvider;
use App\Plugins\PluginLoginProviders;

/**
 * TroopTracker application facade for configuration-based feature toggles.
 *
 * Provides centralized methods to check authentication provider availability
 * and configuration status across the application.
 */
class TroopTracker
{
    /**
     * The configured login provider troopers must sign in with, if any. While
     * one is required, email/password and the other providers are turned off.
     */
    public function requiredLoginProvider(): ?LoginProvider
    {
        return app(PluginLoginProviders::class)->required();
    }

    /**
     * Determine if email/password authentication can be used.
     *
     * Email/password authentication is available unless a login provider
     * is required.
     *
     * @return bool True if email/password authentication can be used, false otherwise
     */
    public function isEmailPasswordAuthEnabled(): bool
    {
        return $this->requiredLoginProvider() === null;
    }

    /**
     * Determine if the monthly support goal widget should be shown.
     *
     * Garrisons that don't take donations through the tracker leave
     * TRACKER_SUPPORT_GOAL empty (or zero) to hide it.
     *
     * @return bool True if a positive support goal is configured, false otherwise
     */
    public function isSupportGoalEnabled(): bool
    {
        return (float) config('tracker.support.goal', 0) > 0;
    }
}
