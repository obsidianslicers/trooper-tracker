<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\Commands;

use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;
use TroopTracker\Plugins\Holonet\Exceptions\LinkRevokedException;
use TroopTracker\Plugins\Holonet\Exceptions\TrackerUnavailableException;
use TroopTracker\Plugins\Holonet\Models\HolonetLink;
use TroopTracker\Plugins\Holonet\Models\HolonetTroop;
use TroopTracker\Plugins\Holonet\Support\TrackerClient;
use TroopTracker\Plugins\Holonet\Support\TroopRecord;

/**
 * Copies a trooper's troop history from a linked tracker. The copy is replaced
 * whole, so troops the other tracker changed or removed follow. When the other
 * tracker can't be reached the old copy stays; when it refuses the link the
 * copy is removed. Returns whether the refresh worked.
 *
 * @method static bool call(HolonetLink $link)
 */
final class RefreshTrackerLink extends Message
{
    public function __construct(private readonly HolonetLink $link) {}

    public function handle(TrackerClient $client): bool
    {
        if ($client->isBusy($this->link->tracker_url))
        {
            $this->link->update([
                HolonetLink::LAST_ERROR => 'The tracker asked us to slow down. Trying again later.',
            ]);

            return false;
        }

        try
        {
            $data = $client->history($this->link->tracker_url, $this->link->token);
        }
        catch (LinkRevokedException)
        {
            $this->markRevoked();

            return false;
        }
        catch (TrackerUnavailableException $e)
        {
            $this->link->update([HolonetLink::LAST_ERROR => mb_substr($e->getMessage(), 0, 255)]);

            return false;
        }

        $this->store($data);

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function store(array $data): void
    {
        $limit = (int) config('plugins.holonet.max_troops', 10000);
        $records = collect(array_slice($data['troops'], 0, $limit))
            ->map(fn (mixed $troop): ?array => TroopRecord::toAttributes($troop))
            ->filter()
            ->unique(HolonetTroop::REMOTE_ID);

        $display_name = $data['trooper']['display_name'] ?? null;

        DB::transaction(function () use ($records, $display_name): void
        {
            $this->link->troops()->delete();

            foreach ($records->chunk(500) as $chunk)
            {
                $this->link->troops()->createMany($chunk->all());
            }

            $this->link->update([
                HolonetLink::STATUS => HolonetLink::STATUS_ACTIVE,
                HolonetLink::REFRESHED_AT => now(),
                HolonetLink::LAST_ERROR => null,
                HolonetLink::REMOTE_DISPLAY_NAME => is_string($display_name)
                    ? mb_substr($display_name, 0, 128)
                    : $this->link->remote_display_name,
            ]);
        });
    }

    private function markRevoked(): void
    {
        DB::transaction(function (): void
        {
            $this->link->troops()->delete();
            $this->link->update([
                HolonetLink::STATUS => HolonetLink::STATUS_REVOKED,
                HolonetLink::LAST_ERROR => 'Access was removed on the other tracker.',
            ]);
        });
    }
}
