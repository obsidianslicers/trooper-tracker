<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Enums\EventTrooperStatus;
use App\Messages\Events\Queries\GetEventDisplay;
use App\Messages\Events\Resources\Display\EventDisplayContext;
use App\Messages\Events\Resources\Display\EventDisplayDetails;
use App\Messages\Events\Resources\Display\EventDisplayMySignUps;
use App\Messages\Events\Resources\Display\EventDisplayShift;
use App\Messages\Events\Resources\Display\EventDisplayUpload;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventUpload;
use App\Models\EventWatch;
use App\Models\Trooper;
use App\Plugins\PluginSlots;
use App\Support\SignUpTour;
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
            'uploads' => fn (): array => $this->uploads(),
            'is_watching' => fn (): bool => EventWatch::where(EventWatch::EVENT_ID, $this->event->id)
                ->where(EventWatch::TROOPER_ID, $this->actor->id)
                ->exists(),
            'tools' => fn (): array => $this->tools(),
            'tour' => fn (): array => $this->tour(),
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
     * @return array{instructional: list<array<string, mixed>>, mission_review: list<array<string, mixed>>}
     */
    private function uploads(): array
    {
        $uploads = $this->event->event_uploads()
            ->with(['troopers:id', 'trooper:id,display_name'])
            ->orderBy(EventUpload::ID)
            ->get()
            ->each(fn (EventUpload $upload) => $upload->setRelation('event', $this->event))
            ->map(fn (EventUpload $upload) => (new EventDisplayUpload($upload, $this->actor))->resolve());

        return [
            'instructional' => $uploads->where('is_instructional', true)->values()->all(),
            'mission_review' => $uploads->where('is_instructional', false)->values()->all(),
            'upload_url' => route('events.upload-image', ['event' => $this->event]),
        ];
    }

    /**
     * The moderators' roster tools and the watch toggle.
     *
     * @return array<string, mixed>
     */
    private function tools(): array
    {
        $can_moderate = $this->context()->can_moderate;
        $is_active = $this->event->is_active;

        return [
            'can_moderate' => $can_moderate,
            'roster_csv_url' => $can_moderate
                ? route('events.download-roster-csv', ['event' => $this->event]) : null,
            'share_roster_url' => $can_moderate && $is_active
                ? route('events.share-roster', ['event' => $this->event]) : null,
            'broadcast_url' => $can_moderate && $is_active
                ? route('events.broadcast-message', ['event' => $this->event]) : null,
            'broadcast_status' => EventTrooperStatus::GOING->label(),
            'watch_url' => route('events.toggle-watch', ['event' => $this->event]),
        ];
    }

    /**
     * The "How sign-up works" tour: whether it starts by itself, and what its example shows.
     *
     * @return array<string, mixed>
     */
    private function tour(): array
    {
        $event = $this->context()->event;
        $plans = array_diff_key(
            $event->signUpStatusOptions(EventTrooperStatus::GOING),
            [EventTrooperStatus::CANCELLED->value => true],
        );

        return [
            'auto_start' => SignUpTour::startsFor($this->actor, $event),
            'storage_key' => 'tt.signupTourSeen.'.$this->actor->id,
            'requires_costume' => $event->requiresCostume(),
            'has_plans' => count($plans) > 1,
            'plans' => array_values($plans),
            'going_label' => EventTrooperStatus::GOING->label(),
            'is_brief_pending' => !$this->context()->has_brief_ack,
        ];
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
