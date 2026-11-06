<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Messages\Commands;

use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;
use TroopTracker\Plugins\Federation\Exceptions\LinkRevokedException;
use TroopTracker\Plugins\Federation\Exceptions\TrackerUnavailableException;
use TroopTracker\Plugins\Federation\Models\LinkedTroop;
use TroopTracker\Plugins\Federation\Models\TrackerLink;
use TroopTracker\Plugins\Federation\Support\TrackerClient;
use TroopTracker\Plugins\Federation\Support\TroopRecord;

/**
 * Copies a trooper's troop history from a linked tracker. The copy is replaced
 * whole, so troops the other tracker changed or removed follow. When the other
 * tracker can't be reached the old copy stays; when it refuses the link the
 * copy is removed. Returns whether the refresh worked.
 *
 * @method static bool call(TrackerLink $link)
 */
final class RefreshTrackerLink extends Message
{
    public function __construct(private readonly TrackerLink $link) {}

    public function handle(TrackerClient $client): bool
    {
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
            $this->link->update([TrackerLink::LAST_ERROR => mb_substr($e->getMessage(), 0, 255)]);

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
        $limit = (int) config('plugins.federation.max_troops', 10000);
        $records = collect(array_slice($data['troops'], 0, $limit))
            ->map(fn (mixed $troop): ?array => TroopRecord::toAttributes($troop))
            ->filter()
            ->unique(LinkedTroop::REMOTE_ID);

        $display_name = $data['trooper']['display_name'] ?? null;

        DB::transaction(function () use ($records, $display_name): void
        {
            $this->link->troops()->delete();

            foreach ($records->chunk(500) as $chunk)
            {
                $this->link->troops()->createMany($chunk->all());
            }

            $this->link->update([
                TrackerLink::STATUS => TrackerLink::STATUS_ACTIVE,
                TrackerLink::REFRESHED_AT => now(),
                TrackerLink::LAST_ERROR => null,
                TrackerLink::REMOTE_DISPLAY_NAME => is_string($display_name)
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
                TrackerLink::STATUS => TrackerLink::STATUS_REVOKED,
                TrackerLink::LAST_ERROR => 'Access was removed on the other tracker.',
            ]);
        });
    }
}
