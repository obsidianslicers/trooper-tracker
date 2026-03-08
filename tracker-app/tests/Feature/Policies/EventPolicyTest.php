<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Policies\EventPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_allows_administrator_and_moderator_but_denies_member(): void
    {
        $policy = new EventPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $member = Trooper::factory()->asMember()->create();

        $this->assertTrue($policy->create($administrator));
        $this->assertTrue($policy->create($moderator));
        $this->assertFalse($policy->create($member));
    }

    public function test_update_allows_administrator_for_any_event(): void
    {
        $policy = new EventPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->withNodePath('org.any')->create();
        $event = Event::factory()->withOrganization($organization)->create();

        $this->assertTrue($policy->update($administrator, $event));
    }

    public function test_update_allows_moderator_within_assigned_scope_and_denies_outside_scope(): void
    {
        $policy = new EventPolicy;

        $moderator = Trooper::factory()->asModerator()->create();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $descendant = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();
        $outside = Organization::factory()->withNodePath('org.other')->create();

        TrooperAssignment::factory()
            ->forTrooper($moderator)
            ->forOrganization($root)
            ->asModerator()
            ->create();

        $allowed_event = Event::factory()->withOrganization($descendant)->create();
        $denied_event = Event::factory()->withOrganization($outside)->create();

        $this->assertTrue($policy->update($moderator, $allowed_event));
        $this->assertFalse($policy->update($moderator, $denied_event));
    }

    public function test_delete_restore_and_force_delete_are_denied(): void
    {
        $policy = new EventPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $this->assertFalse($policy->delete($administrator, $event));
        $this->assertFalse($policy->restore($administrator, $event));
        $this->assertFalse($policy->forceDelete($administrator, $event));
    }
}
