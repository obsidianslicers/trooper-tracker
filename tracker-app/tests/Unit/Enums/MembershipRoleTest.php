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

    public function test_administrator_and_staff_officer_have_administrator_access(): void
    {
        $this->assertTrue(MembershipRole::ADMINISTRATOR->hasAdministratorAccess());
        $this->assertTrue(MembershipRole::STAFF_OFFICER->hasAdministratorAccess());
        $this->assertFalse(MembershipRole::MODERATOR->hasAdministratorAccess());
        $this->assertFalse(MembershipRole::MEMBER->hasAdministratorAccess());
    }

    public function test_only_administrator_has_developer_access(): void
    {
        $this->assertTrue(MembershipRole::ADMINISTRATOR->hasDeveloperAccess());
        $this->assertFalse(MembershipRole::STAFF_OFFICER->hasDeveloperAccess());
        $this->assertFalse(MembershipRole::MODERATOR->hasDeveloperAccess());
    }

    public function test_staff_officer_trooper_is_administrator_without_developer_access(): void
    {
        $subject = Trooper::factory()->asStaffOfficer()->create();

        $this->assertTrue($subject->is_administrator);
        $this->assertFalse($subject->has_developer_access);
    }

    public function test_retired_staff_officer_has_no_administrator_access(): void
    {
        $subject = Trooper::factory()->asStaffOfficer()->asRetired()->create();

        $this->assertFalse($subject->is_administrator);
    }
}
