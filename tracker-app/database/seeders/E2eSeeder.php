<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NoticeType;
use App\Enums\OrganizationType;
use App\Messages\Troopers\Commands\Membership\CreateTrooperRequest;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperNotification;
use App\Models\TrooperOrganization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $this->account($legion);
        $this->approvals($legion);
        $this->trooperAdmin($legion);
        $this->costumeCredits($legion);
    }

    /**
     * Costumes Command Staff create, rename and delete; costumes waiting for review or to be
     * revoked; and a do-not-trust trooper whose troops wait for a moderator.
     */
    private function costumeCredits(Organization $legion): void
    {
        $owner = $this->persona('costume-owner@e2e.test', 'E2E Costume Owner', MembershipRole::MEMBER);
        $this->assign($owner, $legion, is_moderator: false);
        $this->trooperCostume($owner, $legion, 'E2E Doomed Armor');
        $this->trooperCostume($owner, $legion, 'E2E Revoked Armor');
        $this->trooperCostume($owner, $legion, 'E2E Pending Armor', approved: false);
        $this->trooperCostume($owner, $legion, 'E2E Refused Armor', approved: false);
        $this->costume($legion, 'E2E Renamed Armor');

        $untrusted = $this->persona('untrusted@e2e.test', 'E2E Untrusted Trooper', MembershipRole::MEMBER, attributes: [
            Trooper::IS_UNTRUSTED => true,
            Trooper::UNTRUSTED_REASON => 'E2E: confirmed troops they missed.',
        ]);
        $this->assign($untrusted, $legion, is_moderator: false);
        $armor = $this->trooperCostume($untrusted, $legion, 'E2E Untrusted Armor');

        foreach (['E2E Verified Parade' => 2, 'E2E Disputed Parade' => 3] as $name => $days_ago)
        {
            $this->pastEvent($legion, $untrusted, $name, $days_ago, $armor);
        }
    }

    /**
     * Troopers Command Staff edit from the trooper admin pages, each owned by one test.
     */
    private function trooperAdmin(Organization $legion): void
    {
        foreach (['E2E Profile Subject', 'E2E Authority Subject', 'E2E Voided Trooper', 'E2E Departed Trooper', 'E2E Untrusted Subject'] as $name)
        {
            $this->assign($this->persona(Str::slug($name).'@e2e.test', $name, MembershipRole::MEMBER), $legion, is_moderator: false);
        }

        $member = $this->persona('membership-subject@e2e.test', 'E2E Membership Subject', MembershipRole::MEMBER);
        $this->assign($member, $legion, is_moderator: false);
        $this->membership($member, $legion, '91111');

        $academy = Organization::where(Organization::NAME, 'Galactic Academy')->firstOrFail();
        $cadet = $this->persona('cadet@e2e.test', 'E2E Cadet', MembershipRole::MEMBER);
        $this->assign($cadet, $academy, is_moderator: false);
        $this->membership($cadet, $academy, 'GA-1');

        $this->persona('guardian@e2e.test', 'E2E Guardian', MembershipRole::MEMBER, attributes: [
            Trooper::DATE_OF_BIRTH => now()->subYears(40),
        ]);

        $this->persona('recruit-target@e2e.test', 'E2E Recruit Target', MembershipRole::MEMBER);
    }

    private function membership(Trooper $trooper, Organization $organization, string $identifier): void
    {
        TrooperOrganization::create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => $identifier,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    /**
     * Applications, club join requests and troopers for permission requests, each owned by
     * one approvals test.
     */
    private function approvals(Organization $legion): void
    {
        $recruit = $this->persona('deny-recruit@e2e.test', 'E2E Recruit To Deny', MembershipRole::MEMBER, MembershipStatus::PENDING, [
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);
        $this->assign($recruit, $legion, is_moderator: false);

        $squad = Organization::where(Organization::NAME, 'E2E Squad')->firstOrFail();

        $joining = $this->persona('club-join@e2e.test', 'E2E Club Joiner', MembershipRole::MEMBER);
        CreateTrooperRequest::call(trooper: $joining, organization: $squad, identifier: '93456');

        $refused = $this->persona('club-refused@e2e.test', 'E2E Club Refused', MembershipRole::MEMBER);
        CreateTrooperRequest::call(trooper: $refused, organization: $squad, identifier: '94567');

        foreach (['E2E Promotion Candidate', 'E2E Promotion Refused'] as $name)
        {
            $candidate = $this->persona(Str::slug($name).'@e2e.test', $name, MembershipRole::MEMBER);
            $this->assign($candidate, $legion, is_moderator: false);
        }
    }

    /**
     * A trooper of their own for the account spec, which changes their profile, settings,
     * costumes and club requests, and asks for their account to be deleted.
     */
    private function account(Organization $legion): void
    {
        $trooper = $this->persona('account@e2e.test', 'E2E Account Trooper', MembershipRole::MEMBER);
        $this->assign($trooper, $legion, is_moderator: false);
        $this->trooperCostume($trooper, $legion, 'E2E Account Armor');
        $this->costume($legion, 'E2E Spare Armor');

        Notice::create([
            Notice::ORGANIZATION_ID => $legion->id,
            Notice::TITLE => 'E2E Armor Inspection Notice',
            Notice::MESSAGE => 'Bring your armor to the next meeting for inspection.',
            Notice::TYPE => NoticeType::INFO,
            Notice::STARTS_AT => now()->subDay(),
            Notice::ENDS_AT => now()->addMonth(),
        ]);
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

        $this->pastEvent($legion, $trooper, 'E2E Past Parade', 1);
    }

    /**
     * A closed troop from a few days ago with the trooper signed up as going, so they can say
     * whether they made it.
     */
    private function pastEvent(Organization $organization, Trooper $trooper, string $name, int $days_ago, ?Costume $costume = null): void
    {
        $start = now()->subDays($days_ago)->setTime(10, 0);
        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::PRIMARY_ORGANIZATION_ID => $organization->id,
            Event::NAME => $name,
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
        $this->canAttend($event, $organization);
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create([
            EventTrooper::ORGANIZATION_ID => $organization->id,
            EventTrooper::COSTUME_ID => $costume?->id,
            EventTrooper::BACKUP_COSTUME_ID => null,
        ]);
    }

    private function trooperCostume(Trooper $trooper, Organization $organization, string $name, bool $approved = true): Costume
    {
        $costume = Costume::create([Costume::NAME => $name]);

        $organization_costume = OrganizationCostume::create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
            TrooperCostume::APPROVED_AT => $approved ? now() : null,
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
