<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Enums\OrganizationType;
use App\Features\Troopers\Queries\GetTrooperNotificationsQuery;
use App\Features\Troopers\Queries\GetTrooperNotificationsQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperNotificationsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_organizations_collection(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        Organization::factory()->count(3)->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $query = new GetTrooperNotificationsQuery($trooper);
        $subject = new GetTrooperNotificationsQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());
    }

    public function test_invoke_adds_selected_property_to_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $query = new GetTrooperNotificationsQuery($trooper);
        $subject = new GetTrooperNotificationsQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());
        // Check that selected property exists and is false by default
        $this->assertFalse($result->first()->selected);
    }

    public function test_invoke_does_not_mark_organizations_without_should_notify(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::SHOULD_NOTIFY => false,
        ]);

        $query = new GetTrooperNotificationsQuery($trooper);
        $subject = new GetTrooperNotificationsQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $found_organization = $result->firstWhere(Organization::ID, $organization->id);
        if ($found_organization)
        {
            $this->assertFalse($found_organization->selected);
        }
    }
}