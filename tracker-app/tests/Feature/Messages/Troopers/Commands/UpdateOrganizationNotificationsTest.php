<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\UpdateOrganizationNotifications;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOrganizationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_existing_assignment_and_creates_missing_assignment(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization_one = Organization::factory()->create();
        $organization_two = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization_one)
            ->withShouldNotify(false)
            ->create();

        $subject = new UpdateOrganizationNotifications(
            trooper: $trooper,
            organization_ids: [
                $organization_one->{Organization::ID},
                $organization_two->{Organization::ID},
            ],
            enabled: true,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization_one->{Organization::ID},
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization_two->{Organization::ID},
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }

    public function test_handle_can_disable_notifications_for_existing_assignment(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withShouldNotify(true)
            ->create();

        $subject = new UpdateOrganizationNotifications(
            trooper: $trooper,
            organization_ids: [$organization->{Organization::ID}],
            enabled: false,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::SHOULD_NOTIFY => false,
        ]);
    }
}
