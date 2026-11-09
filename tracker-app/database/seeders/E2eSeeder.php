<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Known data for the Playwright browser tests (tests/e2e). Every persona signs in with
 * the password "password". Specs look records up by these names, so keep them in step
 * with tests/e2e/support/personas.js.
 */
class E2eSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        if (app()->environment('production'))
        {
            throw new RuntimeException('The browser test seeder must not run in production.');
        }

        $this->call(OrganizationSeeder::class);

        $legion = Organization::where(Organization::NAME, '501st Legion')->firstOrFail();

        $this->persona('admin@e2e.test', 'E2E Administrator', MembershipRole::SYSTEM_OWNER);
        $moderator = $this->persona('moderator@e2e.test', 'E2E Moderator', MembershipRole::MODERATOR);
        $this->persona('member@e2e.test', 'E2E Member', MembershipRole::MEMBER);

        $this->assign($moderator, $legion, is_moderator: true);

        $recruit = $this->persona(
            'recruit@e2e.test',
            'E2E Pending Recruit',
            MembershipRole::MEMBER,
            MembershipStatus::PENDING
        );
        $this->assign($recruit, $legion, is_moderator: false);

        $this->persona('merge-source@e2e.test', 'E2E Merge Source', MembershipRole::MEMBER);
        $this->persona('merge-target@e2e.test', 'E2E Merge Target', MembershipRole::MEMBER);

        $this->costume($legion, 'E2E Duplicate Armor');
        $this->costume($legion, 'E2E Kept Armor');

        $this->openEvent($legion);
    }

    private function persona(
        string $email,
        string $name,
        MembershipRole $role,
        MembershipStatus $status = MembershipStatus::ACTIVE
    ): Trooper {
        return Trooper::factory()->create([
            Trooper::EMAIL => $email,
            Trooper::DISPLAY_NAME => $name,
            Trooper::LEGAL_NAME => $name,
            Trooper::PASSWORD => Hash::make(self::PASSWORD),
            Trooper::MEMBERSHIP_ROLE => $role,
            Trooper::MEMBERSHIP_STATUS => $status,
            Trooper::EMAIL_VERIFIED_AT => now(),
            Trooper::SETUP_COMPLETED_AT => $status === MembershipStatus::ACTIVE ? now() : null,
        ]);
    }

    private function assign(Trooper $trooper, Organization $organization, bool $is_moderator): void
    {
        TrooperAssignment::create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::IS_MODERATOR => $is_moderator,
        ]);
    }

    private function costume(Organization $organization, string $name): void
    {
        $costume = Costume::create([Costume::NAME => $name]);

        OrganizationCostume::create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
    }

    private function openEvent(Organization $organization): void
    {
        $start = now()->addWeeks(2)->setTime(10, 0);

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::NAME => 'E2E Open Troop',
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(4),
        ]);

        EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(4),
        ]);
    }
}
