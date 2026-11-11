<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationType;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperNotification;
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
        $member = $this->persona('member@e2e.test', 'E2E Member', MembershipRole::MEMBER);

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

        $event = $this->openEvent($legion, 'E2E Open Troop');
        $this->eventTypes($legion, $member);

        $this->notification($member, 'E2E Open Troop is open for sign-up', '/events/details/'.$event->id);

        $this->onboarding($legion);
        $this->trooperEvents($legion);
        $this->commandStaffEvents($legion);
    }

    /**
     * Events Command Staff edit, staff, cancel and review, with a trooper already signed up.
     */
    private function commandStaffEvents(Organization $legion): void
    {
        $rostered = $this->persona('rostered@e2e.test', 'E2E Rostered Trooper', MembershipRole::MEMBER);
        $this->assign($rostered, $legion, is_moderator: false);
        $armor = $this->trooperCostume($rostered, $legion, 'E2E Rostered Armor');

        $event = $this->openEvent($legion, 'E2E Command Troop', attributes: [
            Event::TROOPERS_ALLOWED => 10,
            Event::FRIENDS_ALLOWED => 0,
            Event::GUESTS_ALLOWED => 0,
        ]);
        EventTrooper::factory()
            ->forEventShift($event->event_shifts()->first())
            ->forTrooper($rostered)
            ->withCostume($armor)
            ->asGoing()
            ->create([
                EventTrooper::ORGANIZATION_ID => $legion->id,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $this->openEvent($legion, 'E2E Cancel Troop');
        $this->openEvent($legion, 'E2E Copy Source');
    }

    /**
     * A trooper of their own for the event specs (so their sign-up notifications stay out of
     * the member's bell), with a costume to troop in, events to sign up for and acknowledge the
     * brief of, and a past troop to report attendance on.
     */
    private function trooperEvents(Organization $legion): void
    {
        $trooper = $this->persona('trooper@e2e.test', 'E2E Trooper', MembershipRole::MEMBER);
        $this->assign($trooper, $legion, is_moderator: false);
        $this->trooperCostume($trooper, $legion, 'E2E Trooper Armor');

        $friend = $this->persona('friend@e2e.test', 'E2E Trooper Friend', MembershipRole::MEMBER);
        $this->assign($friend, $legion, is_moderator: false);

        $this->openEvent($legion, 'E2E Signup Troop', attributes: [
            Event::FRIENDS_ALLOWED => 2,
            Event::GUESTS_ALLOWED => 2,
            Event::TROOPERS_ALLOWED => 10,
            Event::VENUE => 'E2E Convention Center',
        ]);

        $this->openEvent($legion, 'E2E Briefed Troop', attributes: [
            Event::REQUIRE_MISSION_BRIEF_ACK => true,
            Event::COMMENTS => 'E2E brief: report to the loading dock at nine.',
        ]);

        $start = now()->subDay()->setTime(10, 0);
        $parade = Event::factory()->create([
            Event::ORGANIZATION_ID => $legion->id,
            Event::PRIMARY_ORGANIZATION_ID => $legion->id,
            Event::NAME => 'E2E Past Parade',
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(4),
        ]);
        $shift = EventShift::factory()->create([
            EventShift::EVENT_ID => $parade->id,
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(4),
            EventShift::STATUS => EventStatus::CLOSED,
        ]);
        $this->canAttend($parade, $legion);
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create([
            EventTrooper::ORGANIZATION_ID => $legion->id,
            EventTrooper::COSTUME_ID => null,
            EventTrooper::BACKUP_COSTUME_ID => null,
        ]);
    }

    private function trooperCostume(Trooper $trooper, Organization $organization, string $name): Costume
    {
        $costume = Costume::create([Costume::NAME => $name]);

        $organization_costume = OrganizationCostume::create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
            TrooperCostume::APPROVED_AT => now(),
        ]);

        return $costume;
    }

    /**
     * Troopers stopped partway through joining: an unconfirmed email, setup not done, a denied
     * application, and a visitor whose access ran out.
     */
    private function onboarding(Organization $legion): void
    {
        $garrison = $this->childOrganization($legion, 'E2E Garrison', OrganizationType::REGION);
        $this->childOrganization($garrison, 'E2E Squad', OrganizationType::UNIT);
        Organization::resequenceAll();

        $this->persona('unverified@e2e.test', 'E2E Unverified Trooper', MembershipRole::MEMBER, attributes: [
            Trooper::EMAIL_VERIFIED_AT => null,
        ]);

        $this->persona('setup@e2e.test', 'E2E Setup Trooper', MembershipRole::MEMBER, attributes: [
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $this->persona('denied@e2e.test', 'E2E Denied Recruit', MembershipRole::MEMBER, MembershipStatus::DENIED, [
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);

        $visitor = $this->persona('expired-visitor@e2e.test', 'E2E Expired Visitor', MembershipRole::VISITOR, attributes: [
            Trooper::VISITOR_EXPIRES_AT => now()->subDay(),
        ]);
        $this->assign($visitor, $legion, is_moderator: false);
    }

    private function childOrganization(Organization $parent, string $name, OrganizationType $type): Organization
    {
        return Organization::create([
            Organization::PARENT_ID => $parent->id,
            Organization::NAME => $name,
            Organization::TYPE => $type,
        ]);
    }

    private function notification(Trooper $trooper, string $title, string $url): void
    {
        TrooperNotification::factory()
            ->forTrooper($trooper)
            ->withData(['title' => $title, 'body' => 'Sign up on the event page.', 'url' => $url])
            ->asUnread()
            ->create();
    }

    private function persona(
        string $email,
        string $name,
        MembershipRole $role,
        MembershipStatus $status = MembershipStatus::ACTIVE,
        array $attributes = []
    ): Trooper {
        return Trooper::factory()->create($attributes + [
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

    /**
     * A type that doesn't count, with an open event of its own and a past one the member
     * attended (next to a counted one, for their service record), and types the event type
     * specs reorder, hide and delete. They sort after the seeded defaults.
     */
    private function eventTypes(Organization $organization, Trooper $member): void
    {
        $workshop = $this->eventType('E2E Workshop', 101, is_counted: false, description: 'E2E armor build night.');
        $this->openEvent($organization, 'E2E Workshop Night', $workshop->id);
        $this->attendedEvent($organization, $member, 'E2E Past Workshop', $workshop->id, days_ago: 14);
        $this->attendedEvent($organization, $member, 'E2E Past Hospital Visit', EventType::defaultId(), days_ago: 21);

        $this->eventType('E2E Sort First', 102);
        $this->eventType('E2E Sort Second', 103);
        $this->eventType('E2E Retiring Type', 104);
        $this->eventType('E2E Unused Type', 105);
    }

    private function eventType(string $name, int $sort_order, bool $is_counted = true, ?string $description = null): EventType
    {
        return EventType::create([
            EventType::NAME => $name,
            EventType::DESCRIPTION => $description,
            EventType::SORT_ORDER => $sort_order,
            EventType::IS_COUNTED => $is_counted,
        ]);
    }

    private function attendedEvent(Organization $organization, Trooper $trooper, string $name, int $event_type_id, int $days_ago): void
    {
        $start = now()->subDays($days_ago)->setTime(10, 0);

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::PRIMARY_ORGANIZATION_ID => $organization->id,
            Event::NAME => $name,
            Event::EVENT_TYPE_ID => $event_type_id,
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(4),
        ]);

        $shift = EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(4),
            EventShift::STATUS => EventStatus::CLOSED,
        ]);

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create([
            EventTrooper::ORGANIZATION_ID => $organization->id,
            EventTrooper::COSTUME_ID => null,
            EventTrooper::BACKUP_COSTUME_ID => null,
        ]);
    }

    private function openEvent(
        Organization $organization,
        string $name,
        ?int $event_type_id = null,
        array $attributes = []
    ): Event {
        $start = now()->addWeeks(2)->setTime(10, 0);

        $event = Event::factory()->create($attributes + [
            Event::ORGANIZATION_ID => $organization->id,
            Event::PRIMARY_ORGANIZATION_ID => $organization->id,
            Event::NAME => $name,
            Event::EVENT_TYPE_ID => $event_type_id,
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(4),
        ]);

        EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(4),
        ]);

        $this->canAttend($event, $organization);

        return $event;
    }

    private function canAttend(Event $event, Organization $organization): void
    {
        EventOrganization::create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
    }
}
