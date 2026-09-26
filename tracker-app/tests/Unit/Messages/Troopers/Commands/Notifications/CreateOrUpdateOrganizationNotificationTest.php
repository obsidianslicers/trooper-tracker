<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Notifications;

use App\Messages\Troopers\Commands\Notifications\CreateOrUpdateOrganizationNotification;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrUpdateOrganizationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_existing_assignment(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withShouldNotify(false)
            ->create();

        $subject = new CreateOrUpdateOrganizationNotification(
            trooper_id: $trooper->{Trooper::ID},
            organization_id: $organization->{Organization::ID},
            enabled: true,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }

    public function test_handle_restores_trashed_assignment_and_updates_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withShouldNotify(false)
            ->create();
        $assignment->delete();

        $subject = new CreateOrUpdateOrganizationNotification(
            trooper_id: $trooper->{Trooper::ID},
            organization_id: $organization->{Organization::ID},
            enabled: true,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::SHOULD_NOTIFY => true,
            TrooperAssignment::DELETED_AT => null,
        ]);
    }

    public function test_handle_creates_missing_assignment(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $subject = new CreateOrUpdateOrganizationNotification(
            trooper_id: $trooper->{Trooper::ID},
            organization_id: $organization->{Organization::ID},
            enabled: true,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }
}
