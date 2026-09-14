<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\FaqSection;
use App\Models\Trooper;
use App\Policies\FaqSectionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqSectionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_allows_administrator_and_denies_moderator_or_member(): void
    {
        $policy = new FaqSectionPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $member = Trooper::factory()->asMember()->create();

        $this->assertTrue($policy->create($administrator));
        $this->assertFalse($policy->create($moderator));
        $this->assertFalse($policy->create($member));
    }

    public function test_update_allows_administrator_and_denies_non_administrators(): void
    {
        $policy = new FaqSectionPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $member = Trooper::factory()->asMember()->create();
        $section = FaqSection::factory()->create();

        $this->assertTrue($policy->update($administrator, $section));
        $this->assertFalse($policy->update($moderator, $section));
        $this->assertFalse($policy->update($member, $section));
    }

    public function test_delete_restore_and_force_delete_allow_administrator_but_deny_others(): void
    {
        $policy = new FaqSectionPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $member = Trooper::factory()->asMember()->create();
        $section = FaqSection::factory()->create();

        $this->assertTrue($policy->delete($administrator, $section));
        $this->assertFalse($policy->delete($moderator, $section));
        $this->assertFalse($policy->delete($member, $section));

        $this->assertTrue($policy->restore($administrator, $section));
        $this->assertFalse($policy->restore($moderator, $section));
        $this->assertFalse($policy->restore($member, $section));

        $this->assertTrue($policy->forceDelete($administrator, $section));
        $this->assertFalse($policy->forceDelete($moderator, $section));
        $this->assertFalse($policy->forceDelete($member, $section));
    }
}
