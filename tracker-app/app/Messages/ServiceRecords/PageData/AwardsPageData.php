<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\Troopers\Queries\GetTrooperAwards;
use Hyperdrive\Message;

/**
 * Awards given to troopers in the last few days.
 *
 * @method static array<string, mixed> call(?int $days = 30)
 */
final class AwardsPageData extends Message
{
    public function __construct(private readonly ?int $days = 30) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $days = (int) $this->days;

        return [
            'award_troopers' => GetTrooperAwards::call(lookback: $days),
            'days' => $days,
        ];
    }
}
