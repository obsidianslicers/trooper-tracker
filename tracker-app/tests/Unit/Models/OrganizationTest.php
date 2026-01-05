<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Models\Organization
 */
class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_source_club_returns_self_when_top_level(): void
    {
        // Arrange
        $club = Organization::factory()->create(); // Default is ORGANIZATION (top level)

        // Act
        $source_club = $club->getSourceClub();

        // Assert
        $this->assertSame($club->id, $source_club->id);
    }

    public function test_get_source_club_returns_parent_when_one_level_deep(): void
    {
        // Arrange
        $club = Organization::factory()->create();
        $region = Organization::factory()->region()->create(['parent_id' => $club->id]);

        // Act
        $source_club = $region->getSourceClub();

        // Assert
        $this->assertSame($club->id, $source_club->id);
    }

    public function test_get_source_club_returns_top_level_when_multiple_levels_deep(): void
    {
        // Arrange
        $club = Organization::factory()->create();
        $region = Organization::factory()->region()->create(['parent_id' => $club->id]);
        $unit = Organization::factory()->unit()->create(['parent_id' => $region->id]);

        // Act
        $source_club = $unit->getSourceClub();

        // Assert
        $this->assertSame($club->id, $source_club->id);
    }
}
