<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\EventShare;
use Carbon\Carbon;

class EventShareTest extends TestCase
{
    public function test_get_route_key_name_returns_share_token(): void
    {
        $subject = new EventShare();

        $this->assertSame('share_token', $subject->getRouteKeyName());
    }

    public function test_get_is_viewable_attribute_checks_revoked_and_expiry(): void
    {
        $subject = new EventShare();

        // Explicitly not revoked and no expiry -> viewable
        $subject->is_revoked = false;
        $subject->expires_at = null;
        $this->assertTrue($subject->getIsViewableAttribute());

        // Revoked -> not viewable
        $subject->is_revoked = true;
        $this->assertFalse($subject->getIsViewableAttribute());

        // Not revoked but expired -> not viewable
        $subject->is_revoked = false;
        $subject->expires_at = Carbon::now()->subDay();
        $this->assertFalse($subject->getIsViewableAttribute());
    }
}
