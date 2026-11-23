<?php

declare(strict_types=1);

namespace App\Messages\Awards\PageData;

use App\Messages\Awards\Resources\AwardedTrooperResource;
use App\Messages\Awards\Resources\AwardSummaryResource;
use App\Models\Award;
use App\Models\AwardTrooper;
use Hyperdrive\Message;

/**
 * The troopers who hold an award, newest first.
 *
 * @method static array call(Award $award)
 */
final class ListAwardTroopersPageData extends Message
{
    public function __construct(private readonly Award $award) {}

    public function handle(): array
    {
        $this->award->loadMissing('organization');

        $troopers = $this->award->troopers()
            ->orderByDesc(AwardTrooper::AWARD_DATE)
            ->get();

        return [
            'award' => new AwardSummaryResource($this->award),
            'troopers' => AwardedTrooperResource::collection($troopers),
        ];
    }
}
