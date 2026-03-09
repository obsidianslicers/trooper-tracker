<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use Tests\TestCase;

class HasTrooperOrganizationScopesTest extends TestCase
{
    public function test_trait_exists(): void
    {
        $this->assertTrue(trait_exists('App\Models\Scopes\HasTrooperOrganizationScopes'));
    }
}