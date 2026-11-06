<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\PageData;

use App\Models\Trooper;
use Hyperdrive\Message;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;
use TroopTracker\Plugins\Holonet\Models\TrackerGrant;
use TroopTracker\Plugins\Holonet\Models\TrackerLink;
use TroopTracker\Plugins\Holonet\Support\LinkEligibility;
use TroopTracker\Plugins\Holonet\Support\ThisTracker;

/**
 * The HoloNet page: the trooper's links, the trackers they can still
 * link, and the trackers that can read their history here.
 *
 * @method static array<string, mixed> call(Trooper $trooper)
 */
final class LinkedTrackersPageData extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(TrackerDirectory $directory, ThisTracker $this_tracker): array
    {
        $links = TrackerLink::query()
            ->where(TrackerLink::TROOPER_ID, $this->trooper->id)
            ->withCount('troops')
            ->orderBy(TrackerLink::TRACKER_NAME)
            ->get();

        return [
            'links' => $links,
            'available_trackers' => $directory->others()
                ->except($links->pluck(TrackerLink::TRACKER_URL)->all())
                ->values(),
            'grants' => TrackerGrant::query()
                ->where(TrackerGrant::TROOPER_ID, $this->trooper->id)
                ->orderBy(TrackerGrant::TRACKER_NAME)
                ->get(),
            'can_link' => LinkEligibility::canLink($this->trooper),
            'tracker_name' => $this_tracker->name(),
            'shares_history' => $this_tracker->sharesHistory(),
        ];
    }
}
