<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use Tests\TestCase;

class HasAuditTrailTest extends TestCase
{
    public function test_trait_exists(): void
    {
        $this->assertTrue(trait_exists('App\Models\Concerns\HasAuditTrail'));
    }
}