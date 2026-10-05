<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_organization(): void
    {
        $subject = Organization::factory()->create();

        $this->assertInstanceOf(Organization::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_parent_relationship_returns_belongs_to(): void
    {
        $subject = Organization::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $subject->parent());
    }

    public function test_event_troopers_relationship_returns_has_many(): void
    {
        $subject = Organization::factory()->create();

        $this->assertInstanceOf(HasMany::class, $subject->event_troopers());
    }

    public function test_event_organizations_relationship_returns_has_many(): void
    {
        $subject = Organization::factory()->create();

        $this->assertInstanceOf(HasMany::class, $subject->event_organizations());
    }

    public function test_get_primary_club_returns_top_level_organization(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();

        $this->assertSame($club->id, $unit->getPrimaryClub()->id);
    }

    public function test_get_primary_club_returns_self_when_no_parent(): void
    {
        $club = Organization::factory()->asOrganization()->create();

        $result = $club->getPrimaryClub();

        $this->assertSame($club->id, $result->id);
    }

    public function test_get_primary_club_returns_correct_club_for_region(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();

        $result = $region->getPrimaryClub();

        $this->assertSame($club->id, $result->id);
    }

    public function test_type_cast_works(): void
    {
        $subject = Organization::factory()->asOrganization()->create();

        $this->assertInstanceOf(OrganizationType::class, $subject->{Organization::TYPE});
        $this->assertSame(OrganizationType::ORGANIZATION, $subject->{Organization::TYPE});
    }

    public function test_resequence_all_assigns_sequence_numbers(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();

        Organization::resequenceAll();

        $club->refresh();
        $region->refresh();
        $unit->refresh();

        $this->assertNotNull($club->{Organization::SEQUENCE});
        $this->assertNotNull($region->{Organization::SEQUENCE});
        $this->assertNotNull($unit->{Organization::SEQUENCE});
        $this->assertGreaterThan($club->{Organization::SEQUENCE}, $region->{Organization::SEQUENCE});
        $this->assertGreaterThan($region->{Organization::SEQUENCE}, $unit->{Organization::SEQUENCE});
    }

    public function test_root_id_from_path_returns_first_segment(): void
    {
        $this->assertSame(1, Organization::rootIdFromPath('1:8:16:'));
        $this->assertSame(7, Organization::rootIdFromPath('7:'));
    }

    public function test_root_ids_by_id_maps_each_organization_to_its_root(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $other_club = Organization::factory()->asOrganization()->create();

        $result = Organization::rootIdsById(collect([$club->id, $region->id, $other_club->id]));

        $this->assertSame(
            [$club->id => $club->id, $region->id => $club->id, $other_club->id => $other_club->id],
            $result
        );
    }

    public function test_root_ids_by_id_omits_missing_organizations(): void
    {
        $this->assertSame([], Organization::rootIdsById(collect([999999])));
        $this->assertSame([], Organization::rootIdsById(collect()));
    }

    public function test_root_ids_for_returns_unique_roots(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();

        $result = Organization::rootIdsFor(collect([$club->id, $region->id]));

        $this->assertSame([$club->id], $result->all());
    }
}
