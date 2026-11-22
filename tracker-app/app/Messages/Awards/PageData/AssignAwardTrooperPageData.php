<?php

declare(strict_types=1);

namespace App\Messages\Awards\PageData;

use App\Messages\Awards\Resources\AwardResource;
use App\Models\Award;
use Hyperdrive\Message;

/**
 * The award a trooper form: the award and the date its frequency suggests for today.
 *
 * @method static array call(Award $award)
 */
final class AssignAwardTrooperPageData extends Message
{
    public function __construct(private readonly Award $award) {}

    public function handle(): array
    {
        $this->award->loadMissing('organization');

        return [
            'award' => new AwardResource($this->award),
            'award_date' => $this->award->frequency->normalizeDate(now())->format('Y-m-d'),
        ];
    }
}
