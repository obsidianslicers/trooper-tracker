<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Commands\Merge;

use App\Models\Costume;
use App\Models\EventTrooper;
use Hyperdrive\Message;

/**
 * Repoints event sign-ups that wore (or listed as backup) the source costume to the target.
 *
 * Credited club ids are left alone. When a sign-up ends up with the same primary and
 * backup costume, the redundant backup is cleared.
 *
 * @method static void call(Costume $target_costume, Costume $source_costume)
 */
final class MergeEventTrooperCostumes extends Message
{
    public function __construct(
        private readonly Costume $target_costume,
        private readonly Costume $source_costume,
    ) {}

    public function handle(): void
    {
        $this->clearBackupsThatWillDuplicatePrimary();

        EventTrooper::query()
            ->withTrashed()
            ->where(EventTrooper::COSTUME_ID, $this->source_costume->id)
            ->update([EventTrooper::COSTUME_ID => $this->target_costume->id]);

        EventTrooper::query()
            ->withTrashed()
            ->where(EventTrooper::BACKUP_COSTUME_ID, $this->source_costume->id)
            ->update([EventTrooper::BACKUP_COSTUME_ID => $this->target_costume->id]);
    }

    private function clearBackupsThatWillDuplicatePrimary(): void
    {
        $merged_ids = [$this->target_costume->id, $this->source_costume->id];

        EventTrooper::query()
            ->withTrashed()
            ->whereIn(EventTrooper::COSTUME_ID, $merged_ids)
            ->whereIn(EventTrooper::BACKUP_COSTUME_ID, $merged_ids)
            ->where(fn ($q) => $q
                ->where(EventTrooper::COSTUME_ID, $this->source_costume->id)
                ->orWhere(EventTrooper::BACKUP_COSTUME_ID, $this->source_costume->id))
            ->update([
                EventTrooper::BACKUP_COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ORGANIZATION_IDS => null,
            ]);
    }
}
