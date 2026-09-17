<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Faq;
use App\Models\Trooper;
use App\Policies\FaqPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_allows_administrator_and_denies_moderator_or_member(): void
    {
        $policy = new FaqPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $member = Trooper::factory()->asMember()->create();

        $this->assertTrue($policy->create($administrator));
        $this->assertFalse($policy->create($moderator));
        $this->assertFalse($policy->create($member));
    }

    public function test_update_allows_administrator_and_denies_non_administrators(): void
    {
        $policy = new FaqPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $member = Trooper::factory()->asMember()->create();
        $faq = Faq::factory()->create();

        $this->assertTrue($policy->update($administrator, $faq));
        $this->assertFalse($policy->update($moderator, $faq));
        $this->assertFalse($policy->update($member, $faq));
    }

    public function test_delete_restore_and_force_delete_allow_administrator_but_deny_others(): void
    {
        $policy = new FaqPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $member = Trooper::factory()->asMember()->create();
        $faq = Faq::factory()->create();

        $this->assertTrue($policy->delete($administrator, $faq));
        $this->assertFalse($policy->delete($moderator, $faq));
        $this->assertFalse($policy->delete($member, $faq));

        $this->assertTrue($policy->restore($administrator, $faq));
        $this->assertFalse($policy->restore($moderator, $faq));
        $this->assertFalse($policy->restore($member, $faq));

        $this->assertTrue($policy->forceDelete($administrator, $faq));
        $this->assertFalse($policy->forceDelete($moderator, $faq));
        $this->assertFalse($policy->forceDelete($member, $faq));
    }
}
