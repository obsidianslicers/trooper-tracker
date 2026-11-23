<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\Event;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Records each shift's charity: who it was for, the hours, the funds raised and any notes.
 *
 * Shifts that aren't on the event are skipped.
 *
 * @method static void call(Event $event, array $shifts)
 */
final class UpdateEventCharity extends Message
{
    use ShouldBeTransactional;

    /**
     * @param  array<int|string, array<string, mixed>>  $shifts  Charity input keyed by shift id
     */
    public function __construct(
        private readonly Event $event,
        private readonly array $shifts,
    ) {}

    public function handle(): void
    {
        foreach ($this->shifts as $id => $input)
        {
            $shift = $this->event->event_shifts->firstWhere('id', (int) $id);

            if ($shift === null)
            {
                continue;
            }

            $shift->charity_name = $input['charity_name'] ?? null;
            $shift->charity_hours = filled($input['charity_hours'] ?? null) ? (int) $input['charity_hours'] : null;
            $shift->charity_direct_funds = (float) ($input['charity_direct_funds'] ?? 0);
            $shift->charity_indirect_funds = (float) ($input['charity_indirect_funds'] ?? 0);
            $shift->charity_notes = $input['charity_notes'] ?? null;

            $shift->save();
        }
    }
}
