<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Bus\MagicBus;
use App\Features\Troopers\Queries\GetEventTroopersMissingCreditQuery;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Page data for the missing-credit fix tool.
 *
 * Lists ATTENDED shifts with no visible "Credited To" organization, scoped to the
 * actor's moderator authority (or all shifts for administrators), optionally filtered
 * to a single trooper, and paginated via `offset` for "load more" fetching.
 *
 * @method static array call(Actor $actor, int|null $trooper_id = null, int $offset = 0)
 */
final class MissingCreditsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?int $trooper_id = null,
        private readonly int $offset = 0,
    ) {}

    public function handle(MagicBus $bus): array
    {
        ['rows' => $rows, 'total' => $total] = $bus->send(new GetEventTroopersMissingCreditQuery(
            actor: $this->actor,
            trooper_id: $this->trooper_id,
            offset: $this->offset,
        ));

        $next_offset = $this->offset + $rows->count();

        return [
            'rows' => $rows->values()->all(),
            'total' => $total,
            'next_offset' => $next_offset,
            'has_more' => $next_offset < $total,
            'filtered_trooper' => $this->getFilteredTrooper(),
        ];
    }

    private function getFilteredTrooper(): ?array
    {
        if ($this->trooper_id === null)
        {
            return null;
        }

        $trooper = Trooper::find($this->trooper_id);

        if ($trooper === null)
        {
            return null;
        }

        return [
            'id' => $trooper->id,
            'display_name' => $trooper->display_name,
        ];
    }
}
