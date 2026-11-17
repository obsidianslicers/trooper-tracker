<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Enums\EventStatus;
use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginSlots;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The event's details for the Overview tab, with the choices its form offers.
 *
 * @property Event $resource
 */
class AdminEventOverview extends JsonResource
{
    public function __construct(Event $event, private readonly Trooper $actor)
    {
        parent::__construct($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource;

        return [
            'form' => $this->form($event),
            'organizations' => $this->organizations($event),
            'host_options' => $this->hostOptions($event),
            'event_types' => $this->eventTypes($event),
            'status_options' => $this->options(EventStatus::toArray()),
            'plugin_fields' => app(PluginSlots::class)
                ->render('admin.events.form.fields', ['event' => $event]),
            'display_url' => route('events.display', ['event' => $event]),
            'trooper_stamps' => new TrooperStampsResource($event),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function form(Event $event): array
    {
        return [
            ...$event->only([
                Event::ORGANIZATION_ID, Event::NAME, Event::EVENT_TYPE_ID, Event::EVENT_WEBSITE,
                Event::LATITUDE, Event::LONGITUDE, Event::VENUE, Event::VENUE_ADDRESS,
                Event::CONTACT_NAME, Event::CONTACT_PHONE, Event::CONTACT_EMAIL,
                Event::EXPECTED_ATTENDEES, Event::REQUESTED_NUMBER_CHARACTERS,
                Event::REQUESTED_CHARACTER_TYPES, Event::SHIFTS_ALLOWED, Event::TROOPERS_ALLOWED,
                Event::HANDLERS_ALLOWED, Event::FRIENDS_ALLOWED, Event::GUESTS_ALLOWED,
                Event::AMENITIES, Event::COMMENTS, Event::REFERRED_BY,
            ]),
            Event::STATUS => $event->status->value,
            Event::EVENT_START => $event->event_start?->format('Y-m-d H:i'),
            Event::EVENT_END => $event->event_end?->format('Y-m-d H:i'),
            ...$this->flags($event),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function flags(Event $event): array
    {
        $flags = [
            Event::TENTATIVE_SIGNUPS_ALLOWED, Event::SECURE_STAGING_AREA, Event::PARKING_AVAILABLE,
            Event::ALLOW_BLASTERS, Event::ALLOW_PROPS, Event::ACCESSIBLE,
            Event::REQUIRE_MISSION_BRIEF_ACK,
        ];

        return collect($flags)
            ->mapWithKeys(fn (string $flag): array => [$flag => (bool) $event->{$flag}])
            ->all();
    }

    /**
     * Every club troopers can sign up from, with the event's limits for it.
     *
     * @return array<int, array<string, mixed>>
     */
    private function organizations(Event $event): array
    {
        $limits = $event->organizations()->get()->keyBy(Organization::ID);

        return Organization::ofTypeOrganizations()
            ->orderBy(Organization::NAME)
            ->get()
            ->map(function (Organization $organization) use ($limits): array
            {
                $pivot = $limits->get($organization->id)?->pivot;
                $can_attend = (bool) ($pivot?->{EventOrganization::CAN_ATTEND} ?? false);

                return [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'image_url' => map_image_url(
                        $organization->image_path_sm,
                        'img/icons/organization-32x32.png'
                    ),
                    'can_attend' => $can_attend,
                    'troopers_allowed' => $can_attend ? $pivot->troopers_allowed : null,
                    'handlers_allowed' => $can_attend ? $pivot->handlers_allowed : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * The organizations this trooper can host for, plus the event's own host.
     *
     * @return array<int, array{value:int,label:string}>
     */
    private function hostOptions(Event $event): array
    {
        return Organization::query()
            ->where(fn ($q) => $q->whereIn(
                Organization::ID,
                Organization::eventHostableBy($this->actor)->select(Organization::ID)
            )->orWhere(Organization::ID, $event->organization_id))
            ->orderBy(Organization::SEQUENCE)
            ->get()
            ->map(fn (Organization $organization): array => [
                'value' => $organization->id,
                'label' => str_repeat('— ', max(0, $organization->depth - 1)).$organization->name,
            ])
            ->all();
    }

    /**
     * The types offered, plus the event's own type if it has since been hidden.
     *
     * @return array<int, array<string, mixed>>
     */
    private function eventTypes(Event $event): array
    {
        return EventType::query()
            ->where(fn ($q) => $q->where(EventType::IS_ACTIVE, true)
                ->orWhere(EventType::ID, $event->event_type_id))
            ->ordered()
            ->get()
            ->map(fn (EventType $type): array => [
                'value' => $type->id,
                'label' => $type->name,
                'description' => (string) $type->description,
                'is_counted' => (bool) $type->is_counted,
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $choices
     * @return array<int, array{value:string,label:string}>
     */
    private function options(array $choices): array
    {
        return (new Collection($choices))
            ->map(fn (string $label, string $value): array => compact('value', 'label'))
            ->values()
            ->all();
    }
}
