<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Messages\Events\Resources\Display\EventDisplayFriendForm;
use App\Models\EventShift;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * The choices for adding a trooper to a shift: the club they troop for when that matters, the
 * shift's stations, and their costumes.
 *
 * @method static EventDisplayFriendForm call(EventShift $event_shift, ?int $trooper_id = null)
 */
final class AddTrooperFormPageData extends Message
{
    public function __construct(
        private readonly EventShift $event_shift,
        private readonly ?int $trooper_id = null,
    ) {}

    public function handle(): EventDisplayFriendForm
    {
        $trooper = Trooper::active()->findOrFail((int) $this->trooper_id);

        $this->event_shift->event->loadMissing('event_organizations');
        $this->event_shift->loadMissing('event_shift_stations');

        return new EventDisplayFriendForm($trooper, $this->event_shift);
    }
}
