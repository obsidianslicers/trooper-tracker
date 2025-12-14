<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for HasTrooperOrganizationScopes trait.
 *
 * Note: This trait is currently empty and defines no scopes.
 * This test file exists to maintain test coverage consistency
 * and will be updated when scopes are added to the trait.
 */
class HasTrooperOrganizationScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_trait_exists_on_model(): void
    {
        // Arrange & Act
        $model = new TrooperOrganization();
        $traits = class_uses($model);

        // Assert
        $this->assertContains(
            'App\Models\Scopes\HasTrooperOrganizationScopes',
            $traits,
            'TrooperOrganization model should use HasTrooperOrganizationScopes trait'
        );
    }
}
