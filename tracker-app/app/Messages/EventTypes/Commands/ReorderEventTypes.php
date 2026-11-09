<?php

declare(strict_types=1);

namespace App\Messages\EventTypes\Commands;

use App\Models\EventType;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Sets the order event types are listed in on the event form.
 *
 * @method static void call(array $ordered_ids)
 */
final class ReorderEventTypes extends Message
{
    use ShouldBeTransactional;

    /**
     * @param  array<int, int|string>  $ordered_ids
     */
    public function __construct(
        private readonly array $ordered_ids,
    ) {}

    public function handle(): void
    {
        foreach (array_values($this->ordered_ids) as $position => $id)
        {
            EventType::query()
                ->where(EventType::ID, (int) $id)
                ->update([EventType::SORT_ORDER => $position + 1]);
        }
    }
}
