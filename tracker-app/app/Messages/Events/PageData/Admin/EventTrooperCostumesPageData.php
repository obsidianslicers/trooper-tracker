<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Admin;

use App\Messages\Events\Resources\AddEventTrooperChoices;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * The costume, club and station choices for adding an active trooper to a shift's roster.
 *
 * @method static AddEventTrooperChoices call(Event $event, EventShift $event_shift, int $trooper_id)
 */
final class EventTrooperCostumesPageData extends Message
{
    public function __construct(
        private readonly Event $event,
        private readonly EventShift $event_shift,
        private readonly int $trooper_id,
    ) {}

    public function handle(): AddEventTrooperChoices
    {
        $trooper = Trooper::active()->findOrFail($this->trooper_id);

        $this->event_shift->loadMissing('event_shift_stations');

        return new AddEventTrooperChoices($trooper, $this->event, $this->event_shift);
    }
}
