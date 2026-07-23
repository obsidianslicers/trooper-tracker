<?php

declare(strict_types=1);

namespace Tests\Feature\Policies\Concerns;

use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasTrooperPermissionsTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_administrator_returns_true_only_for_administrator_role(): void
    {
        $policy = new class
        {
            use HasTrooperPermissionsTrait;

            public function checkAdministrator(Trooper $trooper): bool
            {
                return $this->isAdministrator($trooper);
            }
        };

        $administrator = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();

        $this->assertTrue($policy->checkAdministrator($administrator));
        $this->assertFalse($policy->checkAdministrator($moderator));
    }

    public function test_is_moderator_returns_true_only_for_moderator_role(): void
    {
        $policy = new class
        {
            use HasTrooperPermissionsTrait;

            public function checkModerator(Trooper $trooper): bool
            {
                return $this->isModerator($trooper);
            }
        };

        $moderator = Trooper::factory()->asModerator()->create();
        $administrator = Trooper::factory()->asAdministrator()->create();

        $this->assertTrue($policy->checkModerator($moderator));
        $this->assertFalse($policy->checkModerator($administrator));
    }

    public function test_is_administrator_returns_false_when_administrator_is_retired(): void
    {
        $policy = new class
        {
            use HasTrooperPermissionsTrait;

            public function checkAdministrator(Trooper $trooper): bool
            {
                return $this->isAdministrator($trooper);
            }
        };

        $retired_administrator = Trooper::factory()->asAdministrator()->asRetired()->create();

        $this->assertFalse($policy->checkAdministrator($retired_administrator));
    }

    public function test_is_moderator_returns_false_when_moderator_is_retired(): void
    {
        $policy = new class
        {
            use HasTrooperPermissionsTrait;

            public function checkModerator(Trooper $trooper): bool
            {
                return $this->isModerator($trooper);
            }
        };

        $retired_moderator = Trooper::factory()->asModerator()->asRetired()->create();

        $this->assertFalse($policy->checkModerator($retired_moderator));
    }
}
