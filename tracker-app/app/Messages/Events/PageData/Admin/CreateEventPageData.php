<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Admin;

use App\Enums\EventStatus;
use App\Messages\Events\Resources\Admin\ChoiceOptions;
use App\Messages\Events\Resources\Admin\EventOrganizationLimitCollection;
use App\Messages\Events\Resources\Admin\EventTypeOptionCollection;
use App\Messages\Events\Resources\Admin\HostOptionCollection;
use App\Messages\Events\Resources\Admin\NewEventFormResource;
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
            'form' => new NewEventFormResource($event),
            'organizations' => new EventOrganizationLimitCollection(
                Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get()
            ),
            'host_options' => new HostOptionCollection(
                Organization::eventHostableBy($this->actor)->orderBy(Organization::SEQUENCE)->get(),
                with_club: true,
            ),
            'event_types' => new EventTypeOptionCollection(
                EventType::query()->where(EventType::IS_ACTIVE, true)->ordered()->get()
            ),
            'status_options' => new ChoiceOptions(EventStatus::toArray()),
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
}
