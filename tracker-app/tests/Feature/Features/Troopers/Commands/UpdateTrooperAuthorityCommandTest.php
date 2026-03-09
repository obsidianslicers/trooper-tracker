<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Features\Troopers\Commands\UpdateTrooperAuthorityCommand;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see UpdateTrooperAuthorityCommand
 */
class UpdateTrooperAuthorityCommandTest extends TestCase
{
    public function test_constructor_stores_trooper_membership_role_and_valid_data(): void
    {
        $trooper = Trooper::factory()->make([Trooper::ID => 123]);
        $membership_role = MembershipRole::MODERATOR;
        $valid_data = [1 => ['is_moderator' => true], 2 => ['is_moderator' => false]];

        $subject = new UpdateTrooperAuthorityCommand(
            trooper: $trooper,
            membership_role: $membership_role,
            valid_data: $valid_data
        );

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($membership_role, $subject->membership_role);
        $this->assertSame($valid_data, $subject->valid_data);
    }

    public function test_constructor_accepts_string_membership_role(): void
    {
        $trooper = Trooper::factory()->make();
        $membership_role = 'moderator';
        $valid_data = [];

        $subject = new UpdateTrooperAuthorityCommand(
            trooper: $trooper,
            membership_role: $membership_role,
            valid_data: $valid_data
        );

        $this->assertSame($membership_role, $subject->membership_role);
    }
}
