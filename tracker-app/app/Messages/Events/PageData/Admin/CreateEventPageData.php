<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginSlots;
use App\Plugins\PluginSmilies;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for Create Event: a new event's defaults, the organizations the trooper can host for
 * (with the club whose request message each one parses) and the choices the form offers.
 *
 * @method static array<string, mixed> call(Actor $actor, ?int $organization_id = null)
 */
final class CreateEventPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor  The Command Staff member creating the event
     * @param  int|null  $organization_id  The host to start with (from the organization's page)
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?int $organization_id = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $event = $this->newEvent();

        return [
            'form' => $this->form($event),
            'organizations' => $this->organizations(),
            'host_options' => $this->hostOptions(),
            'event_types' => $this->eventTypes(),
            'status_options' => collect(EventStatus::toArray())
                ->map(fn (string $label, string $value): array => compact('value', 'label'))
                ->values()
                ->all(),
            'smilies' => app(PluginSmilies::class)->all(),
            'plugin_fields' => app(PluginSlots::class)
                ->render('admin.events.form.fields', ['event' => $event]),
        ];
    }

    private function newEvent(): Event
    {
        $event = new Event;

        $event->event_type_id = EventType::defaultId();
        $event->status = EventStatus::DRAFT;

        if ($this->organization_id !== null)
        {
            $event->organization_id = Organization::eventHostableBy($this->actor)
                ->findOrFail($this->organization_id)
                ->id;
        }

        return $event;
    }

    /**
     * The form opens with the logistics questions on "Yes", as it always has.
     *
     * @return array<string, mixed>
     */
    private function form(Event $event): array
    {
        $blank = array_fill_keys([
            Event::NAME, Event::EVENT_START, Event::EVENT_END, Event::EVENT_WEBSITE,
            Event::LATITUDE, Event::LONGITUDE, Event::VENUE, Event::VENUE_ADDRESS,
            Event::CONTACT_NAME, Event::CONTACT_PHONE, Event::CONTACT_EMAIL,
            Event::EXPECTED_ATTENDEES, Event::REQUESTED_NUMBER_CHARACTERS,
            Event::REQUESTED_CHARACTER_TYPES, Event::SHIFTS_ALLOWED, Event::TROOPERS_ALLOWED,
            Event::HANDLERS_ALLOWED, Event::FRIENDS_ALLOWED, Event::GUESTS_ALLOWED,
            Event::AMENITIES, Event::COMMENTS, Event::REFERRED_BY,
        ], null);

        return [
            ...$blank,
            Event::ORGANIZATION_ID => $event->organization_id,
            Event::EVENT_TYPE_ID => $event->event_type_id,
            Event::STATUS => $event->status->value,
            Event::TENTATIVE_SIGNUPS_ALLOWED => true,
            Event::SECURE_STAGING_AREA => true,
            Event::PARKING_AVAILABLE => true,
            Event::ALLOW_BLASTERS => true,
            Event::ALLOW_PROPS => true,
            Event::ACCESSIBLE => true,
            Event::REQUIRE_MISSION_BRIEF_ACK => false,
        ];
    }

    /**
     * Every club troopers can sign up from, open to the ones set to attend by default.
     *
     * @return array<int, array<string, mixed>>
     */
    private function organizations(): array
    {
        return Organization::ofTypeOrganizations()
            ->orderBy(Organization::NAME)
            ->get()
            ->map(fn (Organization $organization): array => [
                'id' => $organization->id,
                'name' => $organization->name,
                'image_url' => map_image_url(
                    $organization->image_path_sm,
                    'img/icons/organization-32x32.png'
                ),
                'can_attend' => (bool) $organization->can_attend_default,
                'troopers_allowed' => null,
                'handlers_allowed' => null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value:int,label:string,club:string}>
     */
    private function hostOptions(): array
    {
        return Organization::eventHostableBy($this->actor)
            ->orderBy(Organization::SEQUENCE)
            ->get()
            ->map(fn (Organization $organization): array => [
                'value' => $organization->id,
                'label' => str_repeat('— ', max(0, $organization->depth - 1)).$organization->name,
                'club' => $organization->getPrimaryClub()->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function eventTypes(): array
    {
        return EventType::query()
            ->where(EventType::IS_ACTIVE, true)
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
}
