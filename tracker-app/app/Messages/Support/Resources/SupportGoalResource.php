<?php

declare(strict_types=1);

namespace App\Messages\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * This month's support goal, as the events list shows it: the goal, how far along it is, the
 * message and where to give.
 *
 * Wraps the array GetSupportGoal returns.
 *
 * @property array{goal: float, current: float, progress: float, message: string, url: string|null} $resource
 */
class SupportGoalResource extends JsonResource
{
    /**
     * @return array{goal: float, progress: float, message: string, url: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'goal' => $this->resource['goal'],
            'progress' => $this->resource['progress'],
            'message' => $this->resource['message'],
            'url' => $this->resource['url'],
        ];
    }
}
