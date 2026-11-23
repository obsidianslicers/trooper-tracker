<?php

declare(strict_types=1);

namespace App\Messages\Support\PageData;

use App\Messages\Support\Queries\GetSupportGoal;
use Hyperdrive\Message;

/**
 * How far the garrison is toward its support goal, for the support widget.
 *
 * @method static array<string, mixed> call()
 */
final class SupportGoalWidgetPageData extends Message
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $support = GetSupportGoal::call();

        return [
            'goal' => $support['goal'],
            'current' => $support['current'],
            'progress' => number_format($support['progress'], 0),
            'message' => $support['message'],
        ];
    }
}
