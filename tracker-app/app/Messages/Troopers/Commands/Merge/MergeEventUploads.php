<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\EventUpload;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Merges event uploads from a source trooper into a target trooper.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeEventUploads extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {
        $source_event_uploads = EventUpload::query()
            ->withTrashed()
            ->where(EventUpload::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(EventUpload::ID)
            ->get();

        foreach ($source_event_uploads as $source_event_upload)
        {
            $source_event_upload->trooper_id = $this->target_trooper->id;
            $source_event_upload->save();
        }
    }
}
