<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasAwardTrooperScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_by_trooper_filters_awards_for_specific_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()
            ->withAwards(2)
            ->create();
        $other_trooper = Trooper::factory()
            ->withAwards(1)
            ->create();

        // Act
        $result = AwardTrooper::byTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(2, $result);
    }

    public function test_scope_by_trooper_eager_loads_award(): void
    {
        // Arrange
        $trooper = Trooper::factory()
            ->withAwards(1)
            ->create();

        // Act
        $result = AwardTrooper::byTrooper($trooper->id)->first();

        // Assert
        $this->assertTrue($result->relationLoaded('award'));
    }

    public function test_scope_by_trooper_returns_empty_collection_when_no_awards(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $result = AwardTrooper::byTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(0, $result);
    }
}
