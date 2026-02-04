<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Concerns;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasTrooperPermissionsTraitTest extends TestCase
{
    use RefreshDatabase;

    private TestablePolicy $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TestablePolicy();
    }

    public function test_is_administrator_returns_true_for_administrator_role(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $result = $this->subject->testIsAdministrator($trooper);

        $this->assertTrue($result);
    }

    public function test_is_administrator_returns_false_for_moderator_role(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $result = $this->subject->testIsAdministrator($trooper);

        $this->assertFalse($result);
    }

    public function test_is_administrator_returns_false_for_member_role(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $result = $this->subject->testIsAdministrator($trooper);

        $this->assertFalse($result);
    }

    public function test_is_moderator_returns_true_for_moderator_role(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $result = $this->subject->testIsModerator($trooper);

        $this->assertTrue($result);
    }

    public function test_is_moderator_returns_false_for_administrator_role(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $result = $this->subject->testIsModerator($trooper);

        $this->assertFalse($result);
    }

    public function test_is_moderator_returns_false_for_member_role(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $result = $this->subject->testIsModerator($trooper);

        $this->assertFalse($result);
    }
}

/**
 * Testable class that uses the trait for testing purposes.
 */
class TestablePolicy
{
    use HasTrooperPermissionsTrait;

    public function testIsAdministrator(Trooper $trooper): bool
    {
        return $this->isAdministrator($trooper);
    }

    public function testIsModerator(Trooper $trooper): bool
    {
        return $this->isModerator($trooper);
    }
}
