<?php

declare(strict_types=1);

namespace App\Messages\Awards\Commands;

use App\Events\AwardGranted;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Carbon;

/**
 * @method static AwardTrooper call(Award $award, Trooper $trooper, Carbon $award_date)
 */
final class GrantTrooperAward extends Message
{
    public function __construct(
        private readonly Award $award,
        private readonly Trooper $trooper,
        private readonly Carbon $award_date,
    ) {}

    public function handle(): AwardTrooper
    {
        $award_trooper = new AwardTrooper;

        $award_trooper->award_id = $this->award->id;
        $award_trooper->trooper_id = $this->trooper->id;
        $award_trooper->award_date = $this->award_date;

        $award_trooper->save();

        AwardGranted::dispatch($award_trooper);

        return $award_trooper;
    }
}
