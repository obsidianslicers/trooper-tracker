<?php

declare(strict_types=1);

namespace App\Messages\Awards\PageData;

use App\Messages\Awards\Resources\AwardResource;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
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
            ->get()
            ->map(fn (Trooper $trooper) => [
                'award_trooper_id' => $trooper->pivot->id,
                'display_name' => $trooper->display_name,
                'award_date' => $trooper->pivot->award_date->localFormat('M d, Y'),
            ]);

        return [
            'award' => new AwardResource($this->award),
            'troopers' => $troopers,
        ];
    }
}
