<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a trooper's request for credit for a troop they never signed up for stands.
 */
enum TroopCorrectionStatus: string
{
    use HasEnumHelpers;

    /**
     * Waiting for the event's coordinator to confirm or deny it.
     */
    case AWAITING_COORDINATOR = 'awaiting_coordinator';

    /**
     * In the moderators' Troop Verifications queue: the event has no coordinator, the
     * trooper coordinated it, or the coordinator denied it or didn't answer in time.
     */
    case AWAITING_MODERATOR = 'awaiting_moderator';

    /**
     * At least one club gave the credit.
     */
    case APPROVED = 'approved';

    /**
     * Every club turned it down.
     */
    case DENIED = 'denied';

    public function isPending(): bool
    {
        return in_array($this, self::pending(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function pending(): array
    {
        return [self::AWAITING_COORDINATOR, self::AWAITING_MODERATOR];
    }
}
