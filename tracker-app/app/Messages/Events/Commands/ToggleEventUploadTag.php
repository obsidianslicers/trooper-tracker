<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventUpload;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Tags a trooper in an event photo, or untags them if they were already tagged.
 *
 * @method static bool call(EventUpload $event_upload, Trooper $trooper)
 */
final class ToggleEventUploadTag extends Message
{
    public function __construct(
        private readonly EventUpload $event_upload,
        private readonly Trooper $trooper,
    ) {}

    /**
     * @return bool Whether the trooper is tagged now.
     */
    public function handle(): bool
    {
        //  fully qualified: `id` is ambiguous between tt_troopers and the pivot table
        $is_tagged = $this->event_upload->troopers()
            ->where('tt_troopers.id', $this->trooper->id)
            ->exists();

        if ($is_tagged)
        {
            //  a hard delete, so the photo no longer counts as tagged
            $this->event_upload->troopers()->detach($this->trooper->id);

            return false;
        }

        $this->event_upload->troopers()->attach($this->trooper->id);

        return true;
    }
}
