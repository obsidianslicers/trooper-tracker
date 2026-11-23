<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Messages\Events\Queries\GetSharedEventRoster;
use App\Models\EventShare;
use Hyperdrive\Message;

/**
 * The roster someone shared, for whoever they shared it with.
 *
 * @method static array<string, mixed> call(EventShare $share)
 */
final class SharedRosterPageData extends Message
{
    public function __construct(private readonly EventShare $share) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'share' => $this->share,
            'event' => GetSharedEventRoster::call(event_share: $this->share),
        ];
    }
}
