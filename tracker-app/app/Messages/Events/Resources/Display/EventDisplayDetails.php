<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Helpers\SmiliesRenderer;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginSmilies;
use App\Support\OneTroopPerDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * The event page's details: its header, when and where, who to contact, the mission brief,
 * logistics, limits and charity, and the links to share it or add it to a calendar.
 *
 * @mixin Event
 */
class EventDisplayDetails extends JsonResource
{
    public function __construct(Event $event, private readonly Trooper $viewer)
    {
        parent::__construct($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'header' => $this->header(),
            'organization_name' => $this->organization?->name,
            'status_label' => $this->status->label(),
            'when' => $this->event_shifts->count() === 1 ? $this->time_display : $this->full_date_display,
            'shift_count' => $this->event_shifts->count(),
            'not_counted_notice' => $this->notCountedNotice(),
            'one_troop_per_day_notice' => $this->hasSameDayShifts() ? __('events.'.OneTroopPerDay::NOTICE) : null,
            'venue' => $this->venue(),
            'contact' => $this->contact(),
            'coordinator' => $this->coordinator(),
            'mission_brief' => $this->missionBrief(),
            'logistics' => $this->logistics(),
            'limits' => [
                'expected_attendees' => $this->expected_attendees ?: null,
                'shifts_allowed' => $this->shifts_allowed ?: null,
                'troopers_allowed' => $this->troopers_allowed ?: null,
                'handlers_allowed' => $this->handlers_allowed ?: null,
            ],
            'quotas' => $this->quotas(),
            'requested_characters' => [
                'number' => $this->requested_number_characters ?: null,
                'types' => $this->requested_character_types ?: null,
            ],
            'charity' => $this->charity(),
            'calendar' => $this->calendar(),
            'shares' => $this->shares(),
            'edit_url' => $this->viewer->can('update', $this->resource)
                ? route('admin.events.update', ['event' => $this->resource]) : null,
            'copy_url' => $this->viewer->can('copy', $this->resource)
                ? route('admin.events.copy', ['event' => $this->resource]) : null,
            'uploads_default_to_instructional' => $this->uploadsDefaultToInstructional(),
        ];
    }

    /**
     * @return array{color: string, logo_url: string|null}
     */
    private function header(): array
    {
        $color = match (true)
        {
            (bool) $this->is_locked => 'secondary',
            (bool) $this->at_risk => 'danger',
            default => 'primary',
        };

        return [
            'color' => $color,
            'logo_url' => $this->isCounted()
                ? map_image_url($this->organization?->image_path_sm, 'img/icons/organization-32x32.png')
                : null,
        ];
    }

    /**
     * @return array{name: string|null, address: string|null, map_url: string|null, website: string|null}
     */
    private function venue(): array
    {
        $address = $this->venue_address ?: null;

        return [
            'name' => $this->venue ?: null,
            'address' => $address,
            'map_url' => $address
                ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($address) : null,
            'website' => $this->event_website && Str::isUrl($this->event_website)
                ? $this->event_website : null,
        ];
    }

    /**
     * @return array{name: string, phone: string|null, email: string|null}|null
     */
    private function contact(): ?array
    {
        if (empty($this->contact_name))
        {
            return null;
        }

        return [
            'name' => $this->contact_name,
            'phone' => $this->contact_phone ?: null,
            'email' => $this->contact_email ?: null,
        ];
    }

    /**
     * @return array{name: string, url: string}|null
     */
    private function coordinator(): ?array
    {
        $coordinator = $this->eventCoordinator();

        return $coordinator === null ? null : [
            'name' => $coordinator->display_name,
            'url' => route('service-records.trooper', ['trooper' => $coordinator->id]),
        ];
    }

    /**
     * @return array{html: string, is_required: bool, is_acknowledged: bool, acknowledge_url: string}|null
     */
    private function missionBrief(): ?array
    {
        if (!$this->comments)
        {
            return null;
        }

        $is_required = (bool) $this->require_mission_brief_ack;

        return [
            'html' => SmiliesRenderer::render(Str::markdown($this->comments), $this->smilies()),
            'is_required' => $is_required,
            'is_acknowledged' => $is_required && $this->hasMissionBriefAcknowledgementFor($this->viewer),
            'acknowledge_url' => route('events.ack-mission-brief', ['event' => $this->resource]),
        ];
    }

    /**
     * The smilies a plugin offers, in the shape SmiliesRenderer reads.
     *
     * @return list<array<string, mixed>>
     */
    private function smilies(): array
    {
        return array_map(
            fn (array $smiley): array => [...$smiley, 'smilie_text' => [$smiley['code']]],
            app(PluginSmilies::class)->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function logistics(): array
    {
        return [
            'secure_staging_area' => (bool) $this->secure_staging_area,
            'allow_blasters' => (bool) $this->allow_blasters,
            'allow_props' => (bool) $this->allow_props,
            'parking_available' => (bool) $this->parking_available,
            'accessible' => (bool) $this->accessible,
            'notes_html' => $this->amenities ? Str::markdown($this->amenities) : null,
        ];
    }

    /**
     * Every club, open or not, and whether the open ones have their own limits.
     *
     * @return array{is_unlimited: bool, closed_names: list<string>, clubs: list<array<string, mixed>>}
     */
    private function quotas(): array
    {
        $open = $this->organizations->filter(fn (Organization $o) => $o->pivot->can_attend ?? false);
        $closed = $this->organizations->reject(fn (Organization $o) => $o->pivot->can_attend ?? false);

        return [
            'is_unlimited' => $open->isNotEmpty() && $open->every(
                fn (Organization $o) => $o->pivot->troopers_allowed === null && $o->pivot->handlers_allowed === null
            ),
            'closed_names' => $closed->pluck(Organization::NAME)->values()->all(),
            'clubs' => $this->organizations->map(fn (Organization $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'can_attend' => (bool) ($o->pivot->can_attend ?? false),
                'troopers_allowed' => ($o->pivot->troopers_allowed ?? null) ?: null,
                'handlers_allowed' => ($o->pivot->handlers_allowed ?? null) ?: null,
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function charity(): array
    {
        return $this->event_shifts
            ->filter(fn (EventShift $s) => $s->charity_name || $s->charity_direct_funds > 0
                || $s->charity_indirect_funds > 0 || $s->charity_hours > 0 || $s->charity_notes)
            ->map(fn (EventShift $s) => [
                'id' => $s->id,
                'when' => $s->time_display,
                'name' => $s->charity_name ?: null,
                'direct_funds' => format_funds($s->charity_direct_funds),
                'indirect_funds' => format_funds($s->charity_indirect_funds),
                'hours' => $s->charity_hours ?: null,
                'notes' => $s->charity_notes ?: null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{google: string, yahoo: string, outlook: string, ics: string}
     */
    private function calendar(): array
    {
        $link = $this->createCalendarLink();

        return [
            'google' => $link->google(),
            'yahoo' => $link->yahoo(),
            'outlook' => $link->webOutlook(),
            'ics' => route('events.download-ics', ['event' => $this->resource]),
        ];
    }

    /**
     * @return list<array{provider: string, url: string, icon: string}>
     */
    private function shares(): array
    {
        return EventDisplayShareLinks::for(route('shares.event', ['event' => $this->resource]), $this->name);
    }
}
