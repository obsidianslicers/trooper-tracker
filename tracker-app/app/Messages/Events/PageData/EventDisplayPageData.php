<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Messages\Events\Queries\GetEventDisplay;
use App\Messages\Events\Resources\Display\EventDisplayContext;
use App\Messages\Events\Resources\Display\EventDisplayDetails;
use App\Messages\Events\Resources\Display\EventDisplayMySignUps;
use App\Messages\Events\Resources\Display\EventDisplayShift;
use App\Messages\Events\Resources\Display\EventDisplaySignUpTour;
use App\Messages\Events\Resources\Display\EventDisplayTools;
use App\Messages\Events\Resources\Display\EventDisplayUploads;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventWatch;
use App\Models\Trooper;
use App\Plugins\PluginSlots;
use Closure;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The event page: its details, each shift's roster and Sign Up, the viewer's own sign-ups,
 * the photos, and what plugins add to it.
 *
 * Each part is a closure, so a change on the page reloads only the parts it touched
 * (a sign-up reloads `shifts` and `my_sign_ups`, a photo `uploads`).
 *
 * @method static array<string, Closure|mixed> call(Actor $actor, Event $event)
 */
final class EventDisplayPageData extends Message
{
    public const PLUGIN_SLOTS = [
        'events.display.sections',
        'events.display.discussion',
        'events.display.share-buttons',
    ];

    private ?EventDisplayContext $context = null;

    /**
     * @param  Actor&Trooper  $actor  The trooper viewing the event
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly Event $event,
    ) {}

    /**
     * @return array<string, Closure|mixed>
     */
    public function handle(): array
    {
        return [
            'event' => fn (): EventDisplayDetails => new EventDisplayDetails($this->context()->event, $this->actor),
            'shifts' => fn (): array => $this->shifts(),
            'my_sign_ups' => fn (): EventDisplayMySignUps => new EventDisplayMySignUps($this->context()),
            'uploads' => fn (): EventDisplayUploads => new EventDisplayUploads($this->event, $this->actor),
            'is_watching' => fn (): bool => EventWatch::where(EventWatch::EVENT_ID, $this->event->id)
                ->where(EventWatch::TROOPER_ID, $this->actor->id)
                ->exists(),
            'tools' => fn (): EventDisplayTools => new EventDisplayTools($this->event, $this->context()->can_moderate),
            'tour' => fn (): EventDisplaySignUpTour => new EventDisplaySignUpTour($this->context()),
            'event_slots' => fn (): array => $this->pluginSlots(),
        ];
    }

    private function context(): EventDisplayContext
    {
        return $this->context ??= new EventDisplayContext(
            GetEventDisplay::call(event: $this->event, trooper: $this->actor),
            $this->actor,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function shifts(): array
    {
        $context = $this->context();

        return $context->event->event_shifts
            ->map(fn (EventShift $shift) => (new EventDisplayShift($shift, $context))->resolve())
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function pluginSlots(): array
    {
        $slots = app(PluginSlots::class);
        $context = ['event' => $this->event, 'actor' => $this->actor, 'request' => request()];

        return array_filter($slots->renderMany(self::PLUGIN_SLOTS, $context));
    }
}
