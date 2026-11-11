<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Rules\Admin\Troopers\AdminOnlyStatusRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;
use Tests\TestCase;

class AdminOnlyStatusRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_cannot_set_a_lock_out_status(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = Trooper::factory()->asMember()->create();

        $statuses = [
            MembershipStatus::INVALID,
            MembershipStatus::DEPARTED,
            MembershipStatus::MERGED,
        ];

        foreach ($statuses as $status)
        {
            $validator = $this->validate($moderator, $subject, $status);

            $this->assertTrue($validator->fails(), $status->value);
        }
    }

    public function test_moderator_cannot_clear_a_lock_out_status(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = Trooper::factory()->asMember()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::DEPARTED,
        ]);

        $validator = $this->validate($moderator, $subject, MembershipStatus::ACTIVE);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Only an administrator can change a trooper to or from this status.',
            $validator->errors()->first('membership_status')
        );
    }

    public function test_moderator_can_keep_a_lock_out_status_unchanged(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = Trooper::factory()->asMember()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::INVALID,
        ]);

        $validator = $this->validate($moderator, $subject, MembershipStatus::INVALID);

        $this->assertTrue($validator->passes());
    }

    public function test_moderator_can_set_other_statuses(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = Trooper::factory()->asMember()->create();

        $validator = $this->validate($moderator, $subject, MembershipStatus::RETIRED);

        $this->assertTrue($validator->passes());
    }

    public function test_administrators_can_set_and_clear_lock_out_statuses(): void
    {
        $subject = Trooper::factory()->asMember()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::INVALID,
        ]);

        $admins = [
            Trooper::factory()->asSystemOwner()->create(),
            Trooper::factory()->asStaffOfficer()->create(),
        ];

        foreach ($admins as $admin)
        {
            foreach ([MembershipStatus::DEPARTED, MembershipStatus::ACTIVE] as $status)
            {
                $this->assertTrue($this->validate($admin, $subject, $status)->passes());
            }
        }
    }

    private function validate(
        Trooper $actor,
        Trooper $subject,
        MembershipStatus $status,
    ): ValidatorInstance {
        return Validator::make(
            ['membership_status' => $status->value],
            ['membership_status' => [new AdminOnlyStatusRule($actor, $subject)]]
        );
    }
}
