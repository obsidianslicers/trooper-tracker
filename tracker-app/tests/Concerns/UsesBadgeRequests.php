<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Plugins\PluginPermissionRequests;
use Tests\Fixtures\PermissionRequests\BadgeRequests;

/**
 * Registers the BadgeRequests permission request extension for a test.
 */
trait UsesBadgeRequests
{
    protected function addBadgeRequests(): void
    {
        BadgeRequests::reset();
        $this->app->make(PluginPermissionRequests::class)->add(BadgeRequests::class);
        $this->beforeApplicationDestroyed(fn () => BadgeRequests::reset());
    }
}
