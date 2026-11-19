<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Models\EventUpload;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Event uploads whose full-size photo is no longer on disk.
 *
 * @method static Collection<int, EventUpload> call()
 */
final class GetEventUploadsMissingFiles extends Message
{
    /**
     * @return Collection<int, EventUpload>
     */
    public function handle(): Collection
    {
        return EventUpload::query()
            ->lazyById(500)
            ->reject(fn (EventUpload $upload): bool => $upload->hasOriginalFile())
            ->values()
            ->collect();
    }
}
