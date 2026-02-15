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
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Carbon\Carbon;
use Database\Seeders\FloridaGarrison\Traits\HasEnumMaps;
use Database\Seeders\FloridaGarrison\Traits\HasSquadMaps;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    use HasSquadMaps;
    use HasEnumMaps;

    private $costumes;
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
        $this->costumes = OrganizationCostume::all()->pluck(OrganizationCostume::ID)->toArray();
        $this->squad_maps = $this->getSquadMap();
        $this->organizations = Organization::ofTypeOrganizations()->get();
        $this->trooper_status_map = array_values(array_filter(
            EventTrooperStatus::cases(),
            fn($case) => $case !== EventTrooperStatus::NONE
        ));
        // $this->sign_ups = DB::table('event_sign_up')
        //     ->join('tt_troopers', 'event_sign_up.trooperid', '=', 'tt_troopers.id')
        //     ->join('tt_event_shifts', 'event_sign_up.troopid', '=', 'tt_event_shifts.id')
        //     ->select('event_sign_up.*')
        //     ->get();
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
        $fl_garrison = $this->getOrganization(-1);

        $columns = [
            'limit501st' => '501st Legion',
            'limitRebels' => 'Rebel Legion',
            'limitMando' => 'Mandalorian Mercs',
            'limitDroid' => 'Droid Builders',
            'limitSG' => 'Saber Guild',
            'limitDE' => 'Dark Empire',
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

            if ($legacy->$column > 0)
            {
                $set[EventOrganization::CAN_ATTEND] = true;
                $set[EventOrganization::TROOPERS_ALLOWED] = $legacy->$column == 500 ? null : $legacy->$column;
            }

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

                $shift->key = $key;

                $this->overlayTroopers($legacy_shift->id, $shift->id);

                $shifts->add($shift);
            }
        }
    }

    private function overlayTroopers($legacy_id, $shift_id)
    {
        // $troopers = $this->sign_ups->filter(fn($s) => $s->troopid == $legacy_id);
        // Mission Correction: Fetch signups for THIS specific legacy shift 
        // effectively bypassing the "empty table at start" issue.
        $troopers = DB::table('event_sign_up')
            ->join('tt_troopers', 'event_sign_up.trooperid', '=', 'tt_troopers.id')
            ->join('tt_event_shifts', 'event_sign_up.troopid', '=', 'tt_event_shifts.id')
            ->where('event_sign_up.troopid', $legacy_id)
            ->select('event_sign_up.*')
            ->get();

        foreach ($troopers as $sign_up)
        {
            $et = EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $shift_id)
                ->where(EventTrooper::TROOPER_ID, $sign_up->trooperid)
                ->first();

            if ($et === null)
            {
                //  first record wins unforunately
                $et = new EventTrooper();

                $et->event_shift_id = $shift_id;
                $et->trooper_id = $sign_up->trooperid;
                $et->status = $this->trooper_status_map[$sign_up->status];
                $et->signed_up_at = Carbon::parse($sign_up->signuptime);

                $et->is_handler = isset($this->handler_ids[$sign_up->trooperid]);

                if ($et->is_handler)
                {
                }
                else
                {
                    $et->costume_id = in_array($sign_up->costume, $this->costumes) ? $sign_up->costume : null;
                    $et->backup_costume_id = in_array($sign_up->costume_backup, $this->costumes) ? $sign_up->costume_backup : null;
                }

                if ($sign_up->addedby > 0)
                {
                    if (isset($this->trooper_ids[$sign_up->addedby]))
                    {
                        $et->added_by_trooper_id = $sign_up->addedby;
                    }
                }

                $et->save();
            }
        }
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

        $event->charity_direct_funds = $legacy->charityDirectFunds;
        $event->charity_indirect_funds = $legacy->charityIndirectFunds;
        $event->charity_name = $legacy->charityName;
        $event->charity_hours = $legacy->charityAddHours;
        $event->charity_notes = $legacy->charityNote;
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