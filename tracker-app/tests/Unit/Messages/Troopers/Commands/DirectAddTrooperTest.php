<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Commands\DirectAddTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Notifications\Troopers\DirectlyAddedToClubNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DirectAddTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_trooper_organization_with_active_status(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        (new DirectAddTrooper($trooper, $organization, 'TK-12345'))->handle();

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function test_handle_creates_trooper_assignment_as_member(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        (new DirectAddTrooper($trooper, $organization, 'TK-12345'))->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_handle_clears_existing_member_assignments_in_same_hierarchy(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $sibling = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:200:')->create();
        $target = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:300:')->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($sibling)->asMember()->create();

        // Pre-create the primary club record so the handler can update it without inserting a new row
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($root)->create();

        (new DirectAddTrooper($trooper, $target, null))->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $sibling->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);
    }

    public function test_handle_sends_directly_added_notification(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        (new DirectAddTrooper($trooper, $organization, 'TK-12345'))->handle();

        Notification::assertSentTo($trooper, DirectlyAddedToClubNotification::class);
    }

    public function test_handle_persists_identifier_when_provided(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        (new DirectAddTrooper($trooper, $organization, 'TK-12345'))->handle();

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-12345',
        ]);
    }

    public function test_handle_handles_null_identifier(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        // Pre-create the primary club record so a null identifier doesn't need to INSERT
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();

        (new DirectAddTrooper($trooper, $organization, null))->handle();

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }
}
