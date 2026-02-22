<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Enums\AchievementType;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQueryHandler;
use App\Models\AwardTrooper;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperDonation;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperServiceRecordQueryHandler.
 *
 * Verifies:
 * - Returns complete service record data structure
 * - Loads trooper with achievements
 * - Retrieves organizations ordered by name
 * - Computes service summary with rank, hours, shifts, funds, milestones
 * - Retrieves upcoming and recent event shifts
 * - Retrieves recent donations
 * - Retrieves awards
 */
class GetTrooperServiceRecordQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_array_with_expected_keys(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('trooper', $result);
        $this->assertArrayHasKey('trooper_organizations', $result);
        $this->assertArrayHasKey('service_summary', $result);
        $this->assertArrayHasKey('upcoming_shifts', $result);
        $this->assertArrayHasKey('recent_shifts', $result);
        $this->assertArrayHasKey('recent_donations', $result);
        $this->assertArrayHasKey('awards', $result);
    }

    public function test_invoke_loads_trooper_with_achievements(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertInstanceOf(Trooper::class, $result['trooper']);
        $this->assertEquals($trooper->id, $result['trooper']->id);
        $this->assertTrue($result['trooper']->relationLoaded('trooper_achievements'));
    }

    public function test_invoke_returns_organizations_ordered_by_name(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org_zulu = Organization::factory()->create([Organization::NAME => 'Zulu Garrison']);
        $org_alpha = Organization::factory()->create([Organization::NAME => 'Alpha Garrison']);

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org_zulu->id,
        ]);
        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org_alpha->id,
        ]);

        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $organizations = $result['trooper_organizations'];
        $this->assertCount(2, $organizations);
        $this->assertEquals('Alpha Garrison', $organizations->first()->name);
        $this->assertEquals('Zulu Garrison', $organizations->last()->name);
    }

    public function test_invoke_returns_service_summary_with_expected_keys(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $summary = $result['service_summary'];
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_shifts', $summary);
        $this->assertArrayHasKey('total_hours', $summary);
        $this->assertArrayHasKey('rank', $summary);
        $this->assertArrayHasKey('rank_title', $summary);
        $this->assertArrayHasKey('rank_theme', $summary);
        $this->assertArrayHasKey('direct_funds', $summary);
        $this->assertArrayHasKey('indirect_funds', $summary);
        $this->assertArrayHasKey('milestones', $summary);
    }

    public function test_invoke_retrieves_upcoming_event_shifts(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $future_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addDays(7),
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $future_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);

        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $upcoming_shifts = $result['upcoming_shifts'];
        $this->assertCount(1, $upcoming_shifts);
        $this->assertEquals($future_shift->id, $upcoming_shifts->first()->id);
    }

    public function test_invoke_retrieves_recent_event_shifts(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $past_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->subDays(7),
            EventShift::STATUS => \App\Enums\EventStatus::CLOSED,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $past_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);

        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $recent_shifts = $result['recent_shifts'];
        $this->assertCount(1, $recent_shifts);
        $this->assertEquals($past_shift->id, $recent_shifts->first()->id);
    }

    public function test_invoke_filters_shifts_older_than_one_year(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        $old_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->subYears(2),
            EventShift::STATUS => \App\Enums\EventStatus::CLOSED,
        ]);
        $recent_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->subMonths(6),
            EventShift::STATUS => \App\Enums\EventStatus::CLOSED,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $old_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $recent_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);

        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        // Old shift should not appear in either upcoming or recent
        $all_shifts = $result['upcoming_shifts']->merge($result['recent_shifts']);
        $this->assertCount(1, $all_shifts);
        $this->assertEquals($recent_shift->id, $all_shifts->first()->id);
    }

    public function test_invoke_retrieves_recent_donations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $donation = TrooperDonation::factory()->create([
            TrooperDonation::TROOPER_ID => $trooper->id,
            TrooperDonation::CREATED_AT => now()->subMonths(3),
        ]);

        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $donations = $result['recent_donations'];
        $this->assertCount(1, $donations);
        $this->assertEquals($donation->id, $donations->first()->id);
    }

    public function test_invoke_filters_donations_older_than_one_year(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $old_donation = TrooperDonation::factory()->create([
            TrooperDonation::TROOPER_ID => $trooper->id,
            TrooperDonation::CREATED_AT => now()->subYears(2),
        ]);
        $recent_donation = TrooperDonation::factory()->create([
            TrooperDonation::TROOPER_ID => $trooper->id,
            TrooperDonation::CREATED_AT => now()->subMonths(3),
        ]);

        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $donations = $result['recent_donations'];
        $this->assertCount(1, $donations);
        $this->assertEquals($recent_donation->id, $donations->first()->id);
    }

    public function test_invoke_retrieves_awards(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $award = AwardTrooper::factory()->create([
            AwardTrooper::TROOPER_ID => $trooper->id,
        ]);

        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $awards = $result['awards'];
        $this->assertCount(1, $awards);
        $this->assertEquals($award->id, $awards->first()->id);
    }

    public function test_invoke_returns_empty_collections_for_trooper_with_no_data(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result['trooper_organizations']);
        $this->assertCount(0, $result['upcoming_shifts']);
        $this->assertCount(0, $result['recent_shifts']);
        $this->assertCount(0, $result['recent_donations']);
        $this->assertCount(0, $result['awards']);
    }

    public function test_invoke_transforms_event_shifts_with_costume_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addDays(7),
        ]);

        $costume = Costume::factory()->create();
        $org_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        $query = new GetTrooperServiceRecordQuery($trooper->id);
        $subject = new GetTrooperServiceRecordQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $upcoming_shifts = $result['upcoming_shifts'];
        $this->assertCount(1, $upcoming_shifts);

        $shift_result = $upcoming_shifts->first();
        $this->assertNotNull($shift_result->event_trooper);
        $this->assertEquals($trooper->id, $shift_result->event_trooper->trooper_id);
    }
}