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
 * to a single trooper.
 *
 * @method static array call(Actor $actor, int|null $trooper_id = null)
 */
final class MissingCreditsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?int $trooper_id = null,
    ) {}

    public function handle(MagicBus $bus): array
    {
        $rows = $bus->send(new GetEventTroopersMissingCreditQuery(
            actor: $this->actor,
            trooper_id: $this->trooper_id,
        ));

        return [
            'rows' => $rows->values()->all(),
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
