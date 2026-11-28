<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AwardFrequency;
use App\Enums\EventStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NoticeType;
use App\Enums\OrganizationType;
use App\Messages\Troopers\Commands\Membership\CreateTrooperRequest;
use App\Models\Award;
use App\Models\AwardTrooper;
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
use App\Models\TrooperDonation;
use App\Models\TrooperFriend;
use App\Models\TrooperOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A believable garrison for recording the FAQ's how-to videos (tests/how-to-videos). Every
 * trooper signs in with the password "password". The recording scripts look records up by
 * these names, so keep the two in step.
 */
class HowToVideoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    private const COSTUMES = [
        'TK-Stormtrooper (ANH Stunt)',
        'TS-Shoretrooper',
        'TB-Biker Scout',
        'TI-TIE Fighter Pilot',
        'IC-Imperial Officer',
        'BH-Boba Fett (ESB)',
        'SL-Darth Vader (ANH)',
        'TD-Sandtrooper',
        'TX-Royal Guard',
        'ID-Inquisitor',
    ];

    private const TROOPERS = [
        // display name, legal name, TKID, role, costume indexes
        ['Jordan Vale', 'Jordan Vale', '12345', MembershipRole::SYSTEM_OWNER, [1, 5, 0]],
        ['Alex Chen', 'Alexander Chen', '21877', MembershipRole::MODERATOR, [0, 4]],
        ['Casey Morgan', 'Casey Morgan', '30412', MembershipRole::EVENT_COORDINATOR, [2]],
        ['Sam Rivera', 'Samantha Rivera', '41520', MembershipRole::MEMBER, [0, 3]],
        ['Taylor Brooks', 'Taylor Brooks', '52288', MembershipRole::MEMBER, [7]],
        ['Riley Patel', 'Riley Patel', '60331', MembershipRole::MEMBER, [8]],
        ['Morgan Lee', 'Morgan Lee', '61704', MembershipRole::MEMBER, [6]],
        ['Jamie Ortiz', 'Jamie Ortiz', '70925', MembershipRole::MEMBER, [9, 0]],
        ['Drew Kim', 'Drew Kim', '73410', MembershipRole::MEMBER, [1]],
        ['Avery Scott', 'Avery Scott', '80056', MembershipRole::MEMBER, [2, 3]],
        ['Quinn Harper', 'Quinn Harper', '84112', MembershipRole::MEMBER, [0]],
        ['Reese Walker', 'Reese Walker', '88340', MembershipRole::HANDLER, []],
        //  a new trooper, for the profile video
        ['Kai Nakamura', 'Kai Nakamura', '89211', MembershipRole::MEMBER, []],
    ];

    private const PAST_EVENTS = [
        // name, days ago, type, troopers (first n of the list), direct funds
        ['Harbor Mall Toy Drive', 9, 'Charity', 8, 2400],
        ['Sunrise Children\'s Hospital Visit', 16, 'Hospital', 5, 0],
        ['Founders Day Parade', 23, 'Regular', 10, 0],
        ['Comic Con Sunrise', 37, 'Convention', 9, 0],
        ['Library Reading Day', 44, 'Public Relations', 4, 0],
        ['Charity Car Wash', 58, 'Charity', 6, 1150],
        ['Little League Opening Day', 71, 'Regular', 7, 0],
        ['Movie Night in the Park', 85, 'Public Relations', 6, 0],
        ['Pediatric Ward Holiday Visit', 99, 'Hospital', 5, 0],
        ['Spring Fling Festival', 120, 'Regular', 8, 0],
        ['Run for the Kids 5K', 150, 'Charity', 9, 3800],
        ['Science Museum Star Wars Day', 182, 'Public Relations', 7, 0],
    ];

    private const UPCOMING_EVENTS = [
        // name, days ahead, type, hours, venue, address, troopers going
        ['Riverside Children\'s Hospital Visit', 5, 'Hospital', 2, 'Riverside Children\'s Hospital', '1200 Riverside Dr, Springfield', 4],
        ['Armor Build Night', 8, 'Armor Party', 3, 'Hangar 12 Makerspace', '12 Hangar Rd, Springfield', 6],
        ['Downtown Holiday Parade', 12, 'Regular', 4, 'Downtown Springfield', '5th Ave & Main St, Springfield', 9],
        ['Lakeview Comic Expo', 18, 'Convention', 4, 'Lakeview Convention Center', '400 Lakeview Blvd, Springfield', 7],
        ['Star Wars Night at the Ballpark', 25, 'Public Relations', 3, 'Springfield Ballpark', '1 Ballpark Way, Springfield', 5],
        ['Food Bank Holiday Drive', 32, 'Charity', 3, 'Springfield Food Bank', '88 Harbor St, Springfield', 2],
    ];

    /** Troops on the second tracker in the HoloNet video, another garrison's. */
    private const OTHER_GARRISON_EVENTS = [
        ['Alpine Winter Festival', 12, 'Regular', 6, 0],
        ['Summit Children\'s Hospital Visit', 30, 'Hospital', 5, 0],
        ['Mountain Comic Con', 47, 'Convention', 8, 0],
        ['Canyon Charity Ride', 66, 'Charity', 6, 900],
        ['Ridgeview Library Day', 90, 'Public Relations', 4, 0],
    ];

    /** @var array<int, Trooper> */
    private array $troopers = [];

    /** @var array<int, Costume> */
    private array $costumes = [];

    public function run(): void
    {
        if (app()->environment('production'))
        {
            throw new RuntimeException('The how-to video seeder must not run in production.');
        }

        $this->call(OrganizationSeeder::class);

        $legion = Organization::where(Organization::NAME, '501st Legion')->firstOrFail();
        //  HOW_TO_VIDEO_SITE=2 is the other garrison's tracker in the HoloNet video
        $other = (int) env('HOW_TO_VIDEO_SITE', 1) === 2;
        $garrison = $this->childOrganization($legion, $other ? 'Mountain Garrison' : 'Sunrise Garrison', OrganizationType::REGION);
        $squad = $this->childOrganization($garrison, 'Harbor Squad', OrganizationType::UNIT);
        $this->childOrganization($garrison, 'Lakeside Squad', OrganizationType::UNIT);
        $rebels = Organization::where(Organization::NAME, 'Rebel Legion')->firstOrFail();
        $this->childOrganization($rebels, 'Coastal Base', OrganizationType::REGION);
        Organization::resequenceAll();

        foreach (self::COSTUMES as $name)
        {
            $this->costumes[] = $this->costume($legion, $name);
        }
        $this->costume($rebels, 'Luke Skywalker (Bespin)');
        $this->costume($rebels, 'Princess Leia (Hoth)');

        foreach (self::TROOPERS as [$name, $legal, $tkid, $role, $costumes])
        {
            $this->troopers[] = $this->trooper($name, $legal, $tkid, $role, $costumes, $legion, $garrison);
        }

        $this->pastEvents($legion, $other ? self::OTHER_GARRISON_EVENTS : self::PAST_EVENTS);

        if ($other)
        {
            return;
        }

        $this->upcomingEvents($legion);
        $this->recentTroop($legion);
        $this->extras($legion, $garrison, $squad);
    }

    private function trooper(
        string $name,
        string $legal,
        string $tkid,
        MembershipRole $role,
        array $costumes,
        Organization $legion,
        Organization $garrison,
    ): Trooper {
        $trooper = $this->persona($name, $legal, $role);
        $this->assign($trooper, $garrison, is_moderator: $role === MembershipRole::MODERATOR);

        if ($role !== MembershipRole::HANDLER)
        {
            $this->membership($trooper, $legion, $tkid);
        }

        foreach ($costumes as $index)
        {
            $this->trooperCostume($trooper, $legion, $this->costumes[$index]);
        }

        return $trooper;
    }

    private function pastEvents(Organization $legion, array $events): void
    {
        foreach ($events as [$name, $days_ago, $type, $count, $funds])
        {
            $start = CarbonImmutable::now()->subDays($days_ago)->setTime(10, 0);
            $event = $this->event($legion, $name, $type, $start, 4, EventStatus::CLOSED, [
                Event::VENUE => $name,
            ]);
            $shift = $event->event_shifts()->first();
            $shift->update([
                EventShift::CHARITY_DIRECT_FUNDS => $funds,
                EventShift::CHARITY_HOURS => 4,
                EventShift::CHARITY_NAME => $funds > 0 ? 'Make-A-Wish' : null,
            ]);

            foreach (array_slice($this->troopers, 0, $count) as $trooper)
            {
                $this->rosterTrooper($shift, $trooper, $legion, attended: true);
            }
        }
    }

    private function upcomingEvents(Organization $legion): void
    {
        foreach (self::UPCOMING_EVENTS as [$name, $days, $type, $hours, $venue, $address, $going])
        {
            $start = CarbonImmutable::now()->addDays($days)->setTime(10, 0);
            $event = $this->event($legion, $name, $type, $start, $hours, EventStatus::OPEN, [
                Event::VENUE => $venue,
                Event::VENUE_ADDRESS => $address,
                Event::TROOPERS_ALLOWED => 20,
                Event::FRIENDS_ALLOWED => 2,
                Event::GUESTS_ALLOWED => 2,
                Event::COORDINATOR_TROOPER_ID => $this->troopers[2]->id,
                Event::COMMENTS => 'Park in the north lot and meet at the loading dock 45 minutes before start. '
                    .'Changing rooms are available. Water and snacks provided.',
            ]);
            $shift = $event->event_shifts()->first();

            //  Sam Rivera (index 3) isn't signed up, so the videos can sign them up
            $roster = array_values(array_filter(
                $this->troopers,
                fn (Trooper $trooper): bool => $trooper->display_name !== 'Sam Rivera'
                    && $trooper->membership_role !== MembershipRole::HANDLER,
            ));

            foreach (array_slice($roster, 0, $going) as $trooper)
            {
                $this->rosterTrooper($shift, $trooper, $legion, attended: false);
            }
        }
    }

    /**
     * A troop from yesterday that Sam signed up for, so they can say they made it.
     */
    private function recentTroop(Organization $legion): void
    {
        $start = CarbonImmutable::now()->subDay()->setTime(10, 0);
        $event = $this->event($legion, 'Springfield Library Grand Opening', 'Public Relations', $start, 3, EventStatus::CLOSED, [
            Event::VENUE => 'Springfield Public Library',
        ]);
        $shift = $event->event_shifts()->first();
        $shift->update([EventShift::STATUS => EventStatus::CLOSED]);

        foreach ([3, 0, 4] as $index)
        {
            $this->rosterTrooper($shift, $this->troopers[$index], $legion, attended: false);
        }
    }

    private function extras(Organization $legion, Organization $garrison, Organization $squad): void
    {
        [$jordan, , , $sam] = $this->troopers;

        $award = Award::create([
            Award::ORGANIZATION_ID => $garrison->id,
            Award::NAME => 'Trooper of the Month',
            Award::FREQUENCY => AwardFrequency::MONTHLY,
        ]);
        foreach ([[$sam, 20], [$jordan, 50]] as [$trooper, $days_ago])
        {
            AwardTrooper::create([
                AwardTrooper::AWARD_ID => $award->id,
                AwardTrooper::TROOPER_ID => $trooper->id,
                AwardTrooper::AWARD_DATE => now()->subDays($days_ago),
            ]);
        }

        Notice::create([
            Notice::ORGANIZATION_ID => $legion->id,
            Notice::TITLE => 'Holiday parade sign-ups are open',
            Notice::MESSAGE => 'We need 30 troopers for the Downtown Holiday Parade. Grab a spot on the event page!',
            Notice::TYPE => NoticeType::INFO,
            Notice::STARTS_AT => now()->subDay(),
            Notice::ENDS_AT => now()->addMonth(),
        ]);

        TrooperFriend::create([TrooperFriend::TROOPER_ID => $sam->id, TrooperFriend::FRIEND_ID => $this->troopers[4]->id]);

        TrooperDonation::create([
            TrooperDonation::TROOPER_ID => $jordan->id,
            TrooperDonation::AMOUNT => 20,
            TrooperDonation::TXN_ID => 'DEMO-1',
            TrooperDonation::TXN_TYPE => 'web_accept',
        ]);

        $this->waitingForCommandStaff($legion, $garrison, $squad);
    }

    /**
     * A recruit to approve, a club request, and a costume waiting for review.
     */
    private function waitingForCommandStaff(Organization $legion, Organization $garrison, Organization $squad): void
    {
        $recruit = $this->persona('Chris Walker', 'Christopher Walker', MembershipRole::MEMBER, MembershipStatus::PENDING, [
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);
        CreateTrooperRequest::call(trooper: $recruit, organization: $squad, identifier: '91234');

        $joiner = $this->troopers[9];
        CreateTrooperRequest::call(trooper: $joiner, organization: $squad, identifier: '80056');

        $riley = $this->troopers[5];
        $this->trooperCostume($riley, $legion, $this->costumes[4], approved: false);
    }

    private function event(
        Organization $organization,
        string $name,
        string $type,
        CarbonImmutable $start,
        int $hours,
        EventStatus $status,
        array $attributes = [],
    ): Event {
        $event = Event::factory()->create($attributes + [
            Event::ORGANIZATION_ID => $organization->id,
            Event::PRIMARY_ORGANIZATION_ID => $organization->id,
            Event::NAME => $name,
            Event::EVENT_TYPE_ID => EventType::where(EventType::NAME, $type)->value(EventType::ID),
            Event::STATUS => $status,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->addHours($hours),
            Event::LATITUDE => 39.78 + (crc32($name) % 100) / 1000,
            Event::LONGITUDE => -89.65 + (crc32(strrev($name)) % 100) / 1000,
            Event::VENUE_ADDRESS => (crc32($name) % 900 + 100).' Main St, Springfield',
            Event::EVENT_WEBSITE => null,
            Event::CONTACT_NAME => 'Pat Gomez',
            Event::CONTACT_EMAIL => 'events@example.org',
            Event::CONTACT_PHONE => '555-0142',
            Event::REQUESTED_CHARACTER_TYPES => 'Stormtroopers and any Imperial characters',
            Event::AMENITIES => 'Changing room, water, parking',
            Event::REFERRED_BY => null,
            Event::SOURCE => null,
            Event::COMMENTS => null,
        ]);

        EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->addHours($hours),
            EventShift::STATUS => $status,
        ]);

        EventOrganization::create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        return $event;
    }

    private function rosterTrooper(EventShift $shift, Trooper $trooper, Organization $legion, bool $attended): void
    {
        $costume_id = TrooperCostume::query()
            ->where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->whereNotNull(TrooperCostume::APPROVED_AT)
            ->join('tt_organization_costumes', 'tt_organization_costumes.id', '=', 'tt_trooper_costumes.organization_costume_id')
            ->value('tt_organization_costumes.costume_id');

        $factory = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper);
        $factory = $attended ? $factory->asAttended() : $factory->asGoing();

        $factory->create([
            EventTrooper::ORGANIZATION_ID => $legion->id,
            EventTrooper::COSTUME_ID => $costume_id,
            EventTrooper::BACKUP_COSTUME_ID => null,
            EventTrooper::IS_HANDLER => $costume_id === null,
            EventTrooper::COSTUME_ORGANIZATION_IDS => $attended && $costume_id ? [$legion->id] : null,
            EventTrooper::SIGNED_UP_AT => $shift->shift_starts_at->copy()->subWeek(),
        ]);
    }

    private function persona(
        string $name,
        string $legal,
        MembershipRole $role,
        MembershipStatus $status = MembershipStatus::ACTIVE,
        array $attributes = [],
    ): Trooper {
        return Trooper::factory()->create($attributes + [
            Trooper::EMAIL => Str::slug($name, '.').'@example.org',
            Trooper::DISPLAY_NAME => $name,
            Trooper::LEGAL_NAME => $legal,
            Trooper::PHONE => '555-01'.str_pad((string) (crc32($name) % 100), 2, '0', STR_PAD_LEFT),
            Trooper::PASSWORD => Hash::make(self::PASSWORD),
            Trooper::MEMBERSHIP_ROLE => $role,
            Trooper::MEMBERSHIP_STATUS => $status,
            Trooper::EMAIL_VERIFIED_AT => now(),
            Trooper::SETUP_COMPLETED_AT => $status === MembershipStatus::ACTIVE ? now() : null,
            Trooper::DATE_OF_BIRTH => null,
            Trooper::GUARDIAN_ID => null,
            Trooper::IS_UNTRUSTED => false,
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

    private function membership(Trooper $trooper, Organization $organization, string $identifier): void
    {
        TrooperOrganization::create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => $identifier,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    private function costume(Organization $organization, string $name): Costume
    {
        $costume = Costume::create([Costume::NAME => $name]);

        OrganizationCostume::create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        return $costume;
    }

    private function trooperCostume(Trooper $trooper, Organization $organization, Costume $costume, bool $approved = true): void
    {
        $organization_costume = OrganizationCostume::query()
            ->where(OrganizationCostume::ORGANIZATION_ID, $organization->id)
            ->where(OrganizationCostume::COSTUME_ID, $costume->id)
            ->firstOrFail();

        TrooperCostume::create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
            TrooperCostume::APPROVED_AT => $approved ? now() : null,
        ]);
    }

    private function childOrganization(Organization $parent, string $name, OrganizationType $type): Organization
    {
        return Organization::create([
            Organization::PARENT_ID => $parent->id,
            Organization::NAME => $name,
            Organization::TYPE => $type,
        ]);
    }
}
