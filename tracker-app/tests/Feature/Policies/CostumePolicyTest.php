<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Costume;
use App\Models\Trooper;
use App\Policies\CostumePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostumePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_update_require_administrator(): void
    {
        $policy = new CostumePolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();

        $this->assertTrue($policy->create($administrator));
        $this->assertTrue($policy->update($administrator, $costume));

        $this->assertFalse($policy->create($member));
        $this->assertFalse($policy->update($member, $costume));
    }

    public function test_delete_requires_administrator(): void
    {
        $policy = new CostumePolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();

        $this->assertTrue($policy->delete($administrator, $costume));
        $this->assertFalse($policy->delete($member, $costume));
    }

    public function test_restore_and_force_delete_are_denied(): void
    {
        $policy = new CostumePolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create();

        $this->assertFalse($policy->restore($administrator, $costume));
        $this->assertFalse($policy->forceDelete($administrator, $costume));
    }
}
