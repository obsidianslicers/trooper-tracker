<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for HasTrooperAssignmentScopes trait.
 *
 * Note: This trait is currently empty and defines no scopes.
 * This test file exists to maintain test coverage consistency
 * and will be updated when scopes are added to the trait.
 */
class HasTrooperAssignmentScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_trait_exists_on_model(): void
    {
        // Arrange & Act
        $model = new TrooperAssignment();
        $traits = class_uses($model);

        // Assert
        $this->assertContains(
            'App\Models\Scopes\HasTrooperAssignmentScopes',
            $traits,
            'TrooperAssignment model should use HasTrooperAssignmentScopes trait'
        );
    }
}
