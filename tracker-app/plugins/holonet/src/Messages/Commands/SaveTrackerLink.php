<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;
use TroopTracker\Plugins\Holonet\Directory\DirectoryTracker;
use TroopTracker\Plugins\Holonet\Models\TrackerLink;

/**
 * Saves the access key another tracker gave for a trooper's history. Linking the
 * same tracker again replaces the old key.
 *
 * @method static TrackerLink call(Trooper $trooper, DirectoryTracker $tracker, string $token, ?string $remote_display_name = null)
 */
final class SaveTrackerLink extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly DirectoryTracker $tracker,
        private readonly string $token,
        private readonly ?string $remote_display_name = null,
    ) {}

    public function handle(): TrackerLink
    {
        return TrackerLink::query()->updateOrCreate(
            [
                TrackerLink::TROOPER_ID => $this->trooper->id,
                TrackerLink::TRACKER_URL => $this->tracker->url,
            ],
            [
                TrackerLink::TRACKER_NAME => $this->tracker->name,
                TrackerLink::REMOTE_DISPLAY_NAME => $this->remote_display_name !== null
                    ? mb_substr($this->remote_display_name, 0, 128)
                    : null,
                TrackerLink::TOKEN => $this->token,
                TrackerLink::STATUS => TrackerLink::STATUS_ACTIVE,
                TrackerLink::LAST_ERROR => null,
            ],
        );
    }
}
