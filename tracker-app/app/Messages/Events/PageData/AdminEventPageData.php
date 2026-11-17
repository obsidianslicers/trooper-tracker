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
 * Data for the Command Staff event page and its tabs (overview, shifts, charity, roster,
 * uploads and mission review).
 *
 * A save asks only for the sections it changed, so its answer carries just those.
 *
 * @method static array<string, mixed> call()
 */
final class AdminEventPageData extends Message
{
    public const OVERVIEW = 'overview';

    public const SHIFTS = 'shifts';

    public const CHARITY = 'charity';

    public const ROSTER = 'roster';

    public const UPLOADS = 'uploads';

    public const MISSION_REVIEW = 'mission_review';

    /**
     * @param  Actor&Trooper  $actor  The Command Staff member viewing the page
     * @param  Event  $event  The event being managed
     * @param  array<int, string>|null  $sections  The sections to send; null sends them all
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly Event $event,
        private readonly ?array $sections = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $this->event->refresh();

        $builders = [
            self::OVERVIEW => fn (): array => $this->overview(),
            self::SHIFTS => fn (): array => [
                self::SHIFTS => new AdminEventShiftCollection($this->getShifts()),
            ],
            self::CHARITY => fn (): array => [
                self::CHARITY => new AdminEventCharityCollection($this->getShifts()),
            ],
            self::ROSTER => fn (): array => [
                self::ROSTER => new AdminEventRoster(
                    $this->event,
                    $this->actor,
                    $this->getRosterShifts()
                ),
            ],
            self::UPLOADS => fn (): array => [
                self::UPLOADS => new AdminEventUploadCollection($this->getUploads(true)),
            ],
            self::MISSION_REVIEW => fn (): array => [
                self::MISSION_REVIEW => new AdminEventUploadCollection($this->getUploads(false)),
            ],
        ];

        return collect($builders)
            ->only($this->sections ?? array_keys($builders))
            ->flatMap(fn (callable $build): array => $build())
            ->all();
    }

    /**
     * The page header and the Overview tab.
     *
     * @return array<string, mixed>
     */
    private function overview(): array
    {
        return [
            'event_id' => $this->event->id,
            'name' => $this->event->name,
            'status_label' => $this->event->status->label(),
            'is_active' => $this->event->is_active,
            'is_draft' => $this->event->is_draft,
            self::OVERVIEW => new AdminEventOverview($this->event, $this->actor),
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
