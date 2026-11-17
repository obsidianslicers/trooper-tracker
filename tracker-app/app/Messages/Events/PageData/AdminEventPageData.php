<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Messages\Events\Queries\GetTroopersForEventAdmin;
use App\Messages\Events\Resources\AdminEventCharityCollection;
use App\Messages\Events\Resources\AdminEventOverview;
use App\Messages\Events\Resources\AdminEventRoster;
use App\Messages\Events\Resources\AdminEventShiftCollection;
use App\Messages\Events\Resources\AdminEventUploadCollection;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventUpload;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Data for the Command Staff event page and all of its tabs (overview, shifts, charity,
 * roster, uploads and mission review).
 *
 * @method static array<string, mixed> call()
 */
final class AdminEventPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor  The Command Staff member viewing the page
     * @param  Event  $event  The event being managed
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly Event $event,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $this->event->refresh();

        $shifts = $this->getShifts();

        return [
            'event_id' => $this->event->id,
            'name' => $this->event->name,
            'status' => $this->event->status->value,
            'status_label' => $this->event->status->label(),
            'is_active' => $this->event->is_active,
            'is_draft' => $this->event->is_draft,
            'overview' => new AdminEventOverview($this->event, $this->actor),
            'shifts' => new AdminEventShiftCollection($shifts),
            'charity' => new AdminEventCharityCollection($shifts),
            'roster' => new AdminEventRoster($this->event, $this->actor, $this->getRosterShifts()),
            'uploads' => new AdminEventUploadCollection($this->getUploads(true)),
            'mission_review' => new AdminEventUploadCollection($this->getUploads(false)),
        ];
    }

    /**
     * @return Collection<int, EventShift>
     */
    private function getShifts(): Collection
    {
        return $this->event->event_shifts()
            ->with('event_shift_stations')
            ->orderBy(EventShift::SHIFT_STARTS_AT)
            ->get();
    }

    /**
     * @return Collection<int, EventShift>
     */
    private function getRosterShifts(): Collection
    {
        return GetTroopersForEventAdmin::call(
            event: $this->event,
            allowed_org_ids: $this->actor->resolveModeratorOrgIds(),
        );
    }

    /**
     * @return Collection<int, EventUpload>
     */
    private function getUploads(bool $is_administrative): Collection
    {
        return $this->event->event_uploads()
            ->where(EventUpload::IS_ADMINISTRATIVE, $is_administrative)
            ->with('trooper', 'troopers')
            ->latest()
            ->get();
    }
}
