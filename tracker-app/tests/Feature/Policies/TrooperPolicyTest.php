<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Policies\TrooperPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_delete_restore_and_force_delete_are_denied(): void
    {
        $policy = new TrooperPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->asMember()->create();

        $this->assertFalse($policy->create($administrator));
        $this->assertFalse($policy->delete($administrator, $subject));
        $this->assertFalse($policy->restore($administrator, $subject));
        $this->assertFalse($policy->forceDelete($administrator, $subject));
    }

    public function test_update_authority_requires_administrator(): void
    {
        $policy = new TrooperPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = Trooper::factory()->asMember()->create();

        $this->assertTrue($policy->updateAuthority($administrator, $subject));
        $this->assertFalse($policy->updateAuthority($moderator, $subject));
    }

    public function test_view_update_moderate_and_approve_allow_administrator_for_any_subject(): void
    {
        $policy = new TrooperPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->asMember()->create();

        $this->assertTrue($policy->view($administrator, $subject));
        $this->assertTrue($policy->update($administrator, $subject));
        $this->assertTrue($policy->moderate($administrator, $subject));
        $this->assertTrue($policy->approve($administrator, $subject));
    }

    public function test_void_and_unvoid_require_administrator_and_respect_current_status(): void
    {
        $policy = new TrooperPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $active_trooper = Trooper::factory()->asActive()->create();
        $void_trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::INVALID]);

        $this->assertTrue($policy->void($administrator, $active_trooper));
        $this->assertFalse($policy->void($administrator, $void_trooper));
        $this->assertFalse($policy->void($moderator, $active_trooper));

        $this->assertTrue($policy->unvoid($administrator, $void_trooper));
        $this->assertFalse($policy->unvoid($administrator, $active_trooper));
        $this->assertFalse($policy->unvoid($moderator, $void_trooper));
    }

    public function test_mark_rip_and_unmark_rip_require_administrator_and_respect_current_status(): void
    {
        $policy = new TrooperPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $active_trooper = Trooper::factory()->asActive()->create();
        $rip_trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::DEPARTED]);

        $this->assertTrue($policy->markRip($administrator, $active_trooper));
        $this->assertFalse($policy->markRip($administrator, $rip_trooper));
        $this->assertFalse($policy->markRip($moderator, $active_trooper));

        $this->assertTrue($policy->unmarkRip($administrator, $rip_trooper));
        $this->assertFalse($policy->unmarkRip($administrator, $active_trooper));
        $this->assertFalse($policy->unmarkRip($moderator, $rip_trooper));
    }

    public function test_moderator_permissions_follow_assignment_scope(): void
    {
        $policy = new TrooperPolicy;

        $moderator = Trooper::factory()->asModerator()->create();
        $allowed_subject = Trooper::factory()->asMember()->create();
        $denied_subject = Trooper::factory()->asMember()->create();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $descendant = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();
        $outside = Organization::factory()->withNodePath('org.other')->create();

        TrooperAssignment::factory()
            ->forTrooper($moderator)
            ->forOrganization($root)
            ->asModerator()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($allowed_subject)
            ->forOrganization($descendant)
            ->asMember()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($denied_subject)
            ->forOrganization($outside)
            ->asMember()
            ->create();

        $this->assertTrue($policy->view($moderator, $allowed_subject));
        $this->assertTrue($policy->update($moderator, $allowed_subject));
        $this->assertTrue($policy->moderate($moderator, $allowed_subject));
        $this->assertTrue($policy->approve($moderator, $allowed_subject));

        $this->assertFalse($policy->view($moderator, $denied_subject));
        $this->assertFalse($policy->update($moderator, $denied_subject));
        $this->assertFalse($policy->moderate($moderator, $denied_subject));
        $this->assertFalse($policy->approve($moderator, $denied_subject));
    }
}
