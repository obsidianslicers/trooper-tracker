<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\Troopers\Queries\GetTrooperAchievements;
use Hyperdrive\Message;

/**
 * Milestones troopers reached in the last few days.
 *
 * @method static array<string, mixed> call(?int $days = 30)
 */
final class AchievementsPageData extends Message
{
    public function __construct(private readonly ?int $days = 30) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $days = (int) $this->days;

        return [
            'days' => $days,
            'trooper_achievements' => GetTrooperAchievements::call(lookback: $days),
        ];
    }
}
