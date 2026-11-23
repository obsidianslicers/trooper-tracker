<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventTrooper;
use Hyperdrive\Message;

/**
 * Changes, or clears, the backup costume on a trooper's sign-up.
 *
 * @method static void call(EventTrooper $event_trooper, ?int $backup_costume_id)
 */
final class ChangeEventTrooperBackupCostume extends Message
{
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly ?int $backup_costume_id,
    ) {}

    public function handle(): void
    {
        UpdateEventTrooper::call(event_trooper: $this->event_trooper, valid_data: [
            EventTrooper::BACKUP_COSTUME_ID => $this->backup_costume_id,
        ]);
    }
}
