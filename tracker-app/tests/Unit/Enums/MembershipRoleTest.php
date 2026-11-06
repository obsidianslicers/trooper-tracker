<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_and_manager_have_administrator_access(): void
    {
        $this->assertTrue(MembershipRole::ADMINISTRATOR->hasAdministratorAccess());
        $this->assertTrue(MembershipRole::MANAGER->hasAdministratorAccess());
        $this->assertFalse(MembershipRole::MODERATOR->hasAdministratorAccess());
        $this->assertFalse(MembershipRole::MEMBER->hasAdministratorAccess());
    }

    public function test_only_administrator_has_developer_access(): void
    {
        $this->assertTrue(MembershipRole::ADMINISTRATOR->hasDeveloperAccess());
        $this->assertFalse(MembershipRole::MANAGER->hasDeveloperAccess());
        $this->assertFalse(MembershipRole::MODERATOR->hasDeveloperAccess());
    }

    public function test_manager_trooper_is_administrator_without_developer_access(): void
    {
        $subject = Trooper::factory()->asManager()->create();

        $this->assertTrue($subject->is_administrator);
        $this->assertFalse($subject->has_developer_access);
    }

    public function test_retired_manager_has_no_administrator_access(): void
    {
        $subject = Trooper::factory()->asManager()->asRetired()->create();

        $this->assertFalse($subject->is_administrator);
    }
}
