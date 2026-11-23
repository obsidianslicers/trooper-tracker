<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Enums\EventStatus;
use App\Messages\Events\Resources\Admin\ChoiceOptions;
use App\Messages\Events\Resources\Admin\EventOrganizationLimitCollection;
use App\Messages\Events\Resources\Admin\EventTypeOptionCollection;
use App\Messages\Events\Resources\Admin\HostOptionCollection;
use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginSlots;
use App\Plugins\PluginSmilies;
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
            'organizations' => new EventOrganizationLimitCollection(
                Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get(),
                $event->organizations()->get(),
            ),
            'host_options' => new HostOptionCollection($this->hostOrganizations($event)),
            'host_name' => $event->organization?->name,
            'can_change_host' => $this->canChangeHost($event),
            'coordinator_options' => $this->coordinatorOptions($event),
            'coordinator_creator' => __('admin.:name (created the event)', [
                'name' => $event->created_by
                    ? $this->coordinatorLabel($event->created_by)
                    : __("admin.The event's creator"),
            ]),
            'event_types' => new EventTypeOptionCollection($this->eventTypes($event)),
            'status_options' => new ChoiceOptions(EventStatus::toArray()),
            'smilies' => app(PluginSmilies::class)->all(),
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
                Event::COORDINATOR_TROOPER_ID,
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
     * The organizations this trooper can host for, plus the event's own host.
     *
     * @return Collection<int, Organization>
     */
    private function hostOrganizations(Event $event): Collection
    {
        return Organization::query()
            ->where(fn ($q) => $q->whereIn(
                Organization::ID,
                Organization::eventHostableBy($this->actor)->select(Organization::ID)
            )->orWhere(Organization::ID, $event->organization_id))
            ->orderBy(Organization::SEQUENCE)
            ->get();
    }

    /**
     * A coordinator who can't host for the event's organization can't move it either.
     */
    private function canChangeHost(Event $event): bool
    {
        return Organization::eventHostableBy($this->actor)
            ->where(Organization::ID, $event->organization_id)
            ->exists();
    }

    /**
     * Who can be picked to run the event. The creator is the empty choice, not an option.
     *
     * @return array<int, array{value:int,label:string}>
     */
    private function coordinatorOptions(Event $event): array
    {
        return $event->coordinatorCandidates()
            ->reject(fn (Trooper $trooper): bool => $trooper->id === $event->created_id)
            ->map(fn (Trooper $trooper): array => [
                'value' => $trooper->id,
                'label' => $this->coordinatorLabel($trooper),
            ])
            ->sortBy(fn (array $option): string => mb_strtolower($option['label']))
            ->values()
            ->all();
    }

    /**
     * The trooper's name with their username after it, as the roster shows both; just the
     * username when no name is on file (or it's the same).
     */
    private function coordinatorLabel(Trooper $trooper): string
    {
        $name = trim((string) $trooper->legal_name);

        return $name === '' || $name === $trooper->display_name
            ? $trooper->display_name
            : "{$name} ({$trooper->display_name})";
    }

    /**
     * The types offered, plus the event's own type if it has since been hidden.
     *
     * @return Collection<int, EventType>
     */
    private function eventTypes(Event $event): Collection
    {
        return EventType::query()
            ->where(fn ($q) => $q->where(EventType::IS_ACTIVE, true)
                ->orWhere(EventType::ID, $event->event_type_id))
            ->ordered()
            ->get();
    }
}
