<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Database\Seeders\FloridaGarrison\Traits\HasCostumeMaps;
use Database\Seeders\FloridaGarrison\Traits\HasEnumMaps;
use Database\Seeders\FloridaGarrison\Traits\HasSquadMaps;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    use HasSquadMaps;
    use HasClubMaps;
    use HasEnumMaps;
    use HasCostumeMaps;

    private $costume_maps;
    private $costume_club_maps;
    private $costume_specific_club_map;
    private $squad_maps;
    private $trooper_status_map;
    // private $sign_ups;
    private $trooper_ids;
    private $handler_ids;
    private $organizations;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->initData();

        $legacy_events = $this->getLegacyEvents();

        foreach ($legacy_events as $legacy)
        {
            $event = Event::find($legacy->id) ?? new Event(['id' => $legacy->id]);

            $this->overlayEvent($legacy, $event);

            $this->overlayOrganization($legacy, $event);

            $event->save();

            $this->overlayOrganizations($legacy, $event);

            $this->overlayShifts($legacy, $event);
        }
    }

    private function initData()
    {
        $this->trooper_ids = Trooper::all()->pluck(Trooper::ID, Trooper::ID)->toArray();
        $this->handler_ids = Trooper::where(Trooper::MEMBERSHIP_ROLE, MembershipRole::HANDLER)->pluck(Trooper::ID, Trooper::ID)->toArray();
        $this->squad_maps = $this->getSquadMap();
        $this->organizations = Organization::ofTypeOrganizations()->get();
        $this->trooper_status_map = array_values(array_filter(
            EventTrooperStatus::cases(),
            fn($case) => $case !== EventTrooperStatus::NONE
        ));

        $costume_club_maps = $this->getCostumeClubMap();

        $this->costume_club_maps = [];

        foreach ($costume_club_maps as $club)
        {
            $this->costume_club_maps[$club['costume_club_id']] = $club;
        }

        $legacy_costumes = $this->getMappedLegacyCostumes();

        $this->costume_maps = [];

        foreach ($legacy_costumes as $costume_name => $legacy_costume)
        {
            foreach ($legacy_costume['ids'] as $legacy_id)
            {
                $this->costume_maps[$legacy_id] = $legacy_costume;
            }
        }

        $this->costume_specific_club_map = DB::table('costumes')
            ->whereNotNull('club')
            ->where('club', '!=', 4)
            ->pluck('club', 'id')
            ->toArray();
    }

    private function overlayOrganization($legacy, $event)
    {
        $fl_garrison = $this->getOrganization(-1);

        if ($legacy->squad <= 0)
        {
            $event->organization_id = $fl_garrison->id;
            $event->primary_organization_id = $fl_garrison->getPrimaryClub()->id;
        }
        else
        {
            $unit = $this->getOrganization($this->squad_maps[$legacy->squad]['id']);
            $event->organization_id = $unit->id;
            $event->primary_organization_id = $unit->getPrimaryClub()->id;
        }
    }

    private function overlayOrganizations($legacy, $event)
    {
        // $fl_garrison = $this->getOrganization(-1);

        $columns = [
            'limit501st' => '501st Legion',
            'limitRebels' => 'Rebel Legion',
            'limitMando' => 'Mandalorian Mercs',
            'limitDroid' => 'Droid Builders',
            'limitSG' => 'Saber Guild',
            'limitDE' => 'Dark Empire',
            //  ignoring Galactic Academy on seeding
        ];

        foreach ($columns as $column => $name)
        {
            $organization = $this->organizations->firstWhere('name', $name);

            $where = [
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
            ];

            $set = [
                EventOrganization::CAN_ATTEND => $column === 'limit501st',
            ];

            $set[EventOrganization::CAN_ATTEND] = $legacy->$column > 0;
            $set[EventOrganization::TROOPERS_ALLOWED] = $legacy->$column >= 500 ? null : $legacy->$column;

            EventOrganization::updateOrCreate($where, $set);
        }
    }

    private function overlayShifts($legacy, $event)
    {
        $shifts = collect([]);

        foreach ($legacy->shifts as $legacy_shift)
        {
            // Parse the incoming values
            $start = Carbon::parse($legacy_shift->dateStart);
            $end = Carbon::parse($legacy_shift->dateEnd);
            $key = $start->format('Y-m-d H:i');

            $filtered = $shifts->filter(fn($s) => $s->key === $key);

            if ($filtered->count() > 0)
            {
                //  move the duped troop onto the non-first record
                $shift = $filtered->first();

                $this->overlayTroopers($legacy_shift->id, $shift->id);
            }
            else
            {
                $shift = EventShift::find($legacy_shift->id) ?? new EventShift(['id' => $legacy_shift->id]);

                $shift->event_id = $event->id;
                $shift->status = $event->status;

                // Assign normalized values
                $shift->shift_starts_at = $start;
                $shift->shift_ends_at = $end;

                $shift->save();

                $this->overlayShiftCharity($legacy_shift, $shift);

                $shift->key = $key;

                $this->overlayTroopers($legacy_shift->id, $shift->id);

                $shifts->add($shift);
            }
        }
    }

    private function overlayShiftCharity($legacy_shift, EventShift $shift): void
    {
        $shift->charity_direct_funds   = $legacy_shift->charityDirectFunds ?? 0;
        $shift->charity_indirect_funds = $legacy_shift->charityIndirectFunds ?? 0;
        $shift->charity_name           = $legacy_shift->charityName;
        $shift->charity_hours          = $legacy_shift->charityAddHours;
        $shift->charity_notes          = $legacy_shift->charityNote;
        $shift->save();
    }

    private function overlayTroopers($legacy_id, $shift_id)
    {
        // $troopers = $this->sign_ups->filter(fn($s) => $s->troopid == $legacy_id);
        // Mission Correction: Fetch signups for THIS specific legacy shift 
        // effectively bypassing the "empty table at start" issue.
        $legacy_sign_ups = DB::table('event_sign_up')
            ->join('tt_troopers', 'event_sign_up.trooperid', '=', 'tt_troopers.id')
            ->join('tt_event_shifts', 'event_sign_up.troopid', '=', 'tt_event_shifts.id')
            ->where('event_sign_up.troopid', $legacy_id)
            ->select('event_sign_up.*')
            ->get();

        foreach ($legacy_sign_ups as $legacy_sign_up)
        {
            $event_trooper = EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $shift_id)
                ->where(EventTrooper::TROOPER_ID, $legacy_sign_up->trooperid)
                ->first();

            if ($event_trooper === null)
            {
                //  first record wins unforunately
                $event_trooper = new EventTrooper();

                $event_trooper->event_shift_id = $shift_id;
                $event_trooper->trooper_id = $legacy_sign_up->trooperid;
                $event_trooper->status = $this->trooper_status_map[$legacy_sign_up->status];
                $event_trooper->signed_up_at = Carbon::parse($legacy_sign_up->signuptime);

                $event_trooper->is_handler = isset($this->handler_ids[$legacy_sign_up->trooperid]);

                if (!$event_trooper->is_handler)
                {
                    $event_trooper->costume_id = $this->getMappedCostumeId($legacy_sign_up->costume);

                    $event_trooper->costume_organization_ids = $this->getMappedCostumeOrganizationIds($legacy_sign_up->costume);

                    $event_trooper->backup_costume_id = $this->getMappedCostumeId($legacy_sign_up->costume_backup);

                    $event_trooper->backup_costume_organization_ids = $this->getMappedCostumeOrganizationIds($legacy_sign_up->costume_backup);
                }

                if ($legacy_sign_up->addedby > 0)
                {
                    if (isset($this->trooper_ids[$legacy_sign_up->addedby]))
                    {
                        $event_trooper->added_by_trooper_id = $legacy_sign_up->addedby;
                    }
                }

                $event_trooper->save();
            }
        }
    }

    private function getMappedCostumeId($legacy_id): ?int
    {
        $legacy_costume = $this->costume_maps[$legacy_id] ?? null;

        if ($legacy_costume === null)
        {
            return null;
        }

        return $legacy_costume['id'];
    }

    private function getMappedCostumeOrganizationIds($legacy_id): ?array
    {
        if (!isset($this->costume_maps[$legacy_id]))
        {
            return null;
        }

        $specific_club = $this->costume_specific_club_map[$legacy_id] ?? null;

        if ($specific_club === null)
        {
            return null;
        }

        $organization_ids = [];
        $clubs = $this->expandDualClubIds([$specific_club]);

        foreach ($clubs as $club_id)
        {
            $club = $this->costume_club_maps[$club_id] ?? null;

            if ($club !== null)
            {
                $organization_ids[] = $club['id'];
            }
        }

        return $organization_ids ?: null;
    }

    private function overlayEvent($legacy, $event)
    {
        $legacy->name = trim($legacy->name);

        $event->name = $legacy->name;

        $label = is_string($legacy->label) ? (int) $legacy->label : ($legacy->label ?? 0);

        $event->type = $this->eventLabelFromLegacyId($label);

        $event->venue = $legacy->venue;
        $event->venue_address = $legacy->location;
        $event->latitude = $legacy->latitude;
        $event->longitude = $legacy->longitude;
        $event->event_start = $legacy->dateStart;
        $event->event_end = $legacy->dateEnd;
        $event->event_website = $legacy->website;
        $event->expected_attendees = $legacy->numberOfAttend;
        $event->requested_number_characters = $legacy->requestedNumber;
        $event->requested_character_types = $legacy->requestedCharacter;
        $event->secure_staging_area = $legacy->secureChanging ?? false;
        $event->allow_blasters = $legacy->blasters ?? false;
        $event->allow_props = $legacy->lightsabers ?? false;
        $event->parking_available = $legacy->parking ?? false;
        $event->accessible = $legacy->mobility ?? false;
        $event->amenities = $legacy->amenities;
        $event->comments = $this->toMarkdown($legacy->comments);
        $event->referred_by = $legacy->referred;

        if ($legacy->limitedEvent)
        {
            $event->troopers_allowed = $legacy->limitTotalTroopers;
            $event->handlers_allowed = $legacy->limitHandlers;
        }
        else
        {
            $event->troopers_allowed = null;
        }

        $event->friends_allowed = $legacy->limitFriends ?? null;
        $event->tentative_signups_allowed = (bool) $legacy->allowTentative;

        $event->status = $legacy->closed ? EventStatus::CLOSED : EventStatus::OPEN;

        // Carry legacy forum references when present; otherwise clear to null.
        $event->thread_id = $this->toNullablePositiveInt($legacy->thread_id ?? null);
        $event->post_id = $this->toNullablePositiveInt($legacy->post_id ?? null);
    }

    private function toNullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value))
        {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function getOrganization($id)
    {
        $organizations = once(fn() => Organization::all()->keyBy('id'));

        if ($id <= 0)
        {
            return $organizations->filter(fn($org) => $org->name === 'Florida Garrison')->first();
        }

        return $organizations[$id];
    }

    private function getLegacyEvents()
    {
        $all = DB::table('events')
            // ->where(function ($query)
            // {
            //     $query->where('id', 10025)
            //         ->orWhere('link', 10025);
            // })
            // ->where('id', 11296)
            ->orderBy('id')
            ->orderBy('link')
            ->get();

        $events = [];

        foreach ($all->filter(fn($x) => $x->link === 0) as $event)
        {
            $event->shifts = collect([$event]);

            $events[$event->id] = $event;
        }

        foreach ($all->filter(fn($x) => $x->link > 0) as $event)
        {
            $main_event = $events[$event->link];

            $main_event->shifts->add($event);
        }

        return collect($events);
    }

    private function toMarkdown($text): string
    {
        // Normalize newlines
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Protect code blocks first (so inner tags aren't transformed)
        // [code]...[/code] -> triple backticks (preserve content verbatim)
        $text = preg_replace_callback('/\[code\](.*?)\[\/code\]/is', function ($m)
        {
            $code = trim($m[1], "\n");
            return "\n```" . "\n" . $code . "\n" . "```\n";
        }, $text);

        // Quotes
        // [quote]...[/quote] -> blockquote
        // [quote=Name]...[/quote] -> blockquote with header
        $text = preg_replace_callback('/\[quote(?:=([^\]]+))?\](.*?)\[\/quote\]/is', function ($m)
        {
            $who = isset($m[1]) ? trim($m[1]) : null;
            $body = trim($m[2]);
            $quote = '';
            if ($who)
            {
                $quote .= '> **' . $who . ':**' . "\n";
            }
            foreach (explode("\n", $body) as $line)
            {
                $quote .= '> ' . $line . "\n";
            }
            return "\n" . rtrim($quote) . "\n";
        }, $text);

        // Images
        // [img]url[/img] -> ![](url)
        $text = preg_replace('/\[img\]\s*(.*?)\s*\[\/img\]/is', '![]($1)', $text);

        // Links
        // [url]http://example[/url] -> <http://example>
        // [url=http://example]text[/url] -> [text](http://example)
        $text = preg_replace('/\[url\]\s*(.*?)\s*\[\/url\]/is', '<$1>', $text);
        $text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/is', '[$2]($1)', $text);

        // Basic inline styles
        $replacements = [
            // [b]bold[/b] -> **bold**
            '/\[b\](.*?)\[\/b\]/is' => '**$1**',
            // [i]italic[/i] -> _italic_
            '/\[i\](.*?)\[\/i\]/is' => '_$1_',
            // [u]underline[/u] -> use <u> for lack of native MD underline
            '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
            // [s]strike[/s] -> ~~strike~~
            '/\[s\](.*?)\[\/s\]/is' => '~~$1~~',
            // [spoiler]...[/spoiler] -> collapsible via HTML details
            '/\[spoiler\](.*?)\[\/spoiler\]/is' => "<details><summary>spoiler</summary>\n$1\n</details>",
        ];
        foreach ($replacements as $pattern => $replace)
        {
            $text = preg_replace($pattern, $replace, $text);
        }

        // Size and color (map to emphasis or inline HTML; Markdown has no native color/size)
        $text = preg_replace('/\[size=(\d+)\](.*?)\[\/size\]/is', '<span style="font-size:$1px">$2</span>', $text);
        $text = preg_replace('/\[color=([#a-zA-Z0-9]+)\](.*?)\[\/color\]/is', '<span style="color:$1">$2</span>', $text);

        // Lists
        // Unordered: [list] [*] item ... [/list]
        $text = preg_replace_callback('/\[list\](.*?)\[\/list\]/is', function ($m)
        {
            $items = preg_split('/\s*\[\*\]\s*/', trim($m[1]));
            $items = array_filter($items, fn($i) => $i !== '');
            if (!$items)
                return '';
            return "\n" . implode("\n", array_map(fn($i) => '- ' . trim($i), $items)) . "\n";
        }, $text);

        // Ordered: [list=1] or [list=decimal|alpha] -> numbered list
        $text = preg_replace_callback('/\[list=([^\]]+)\](.*?)\[\/list\]/is', function ($m)
        {
            $items = preg_split('/\s*\[\*\]\s*/', trim($m[2]));
            $items = array_filter($items, fn($i) => $i !== '');
            if (!$items)
                return '';
            // Markdown doesn’t support starting index; keep simple 1..n
            return "\n" . implode("\n", array_map(fn($i) => '1. ' . trim($i), $items)) . "\n";
        }, $text);

        // Linebreaks: [br] -> newline
        $text = preg_replace('/\[br\]/i', "\n", $text);

        // Remove residual BBCode tags we don’t handle (light-touch)
        $text = preg_replace('/\[(\/?)(left|right|center)\]/i', '', $text);

        // Trim excess blank lines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}