<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What happened when someone changed a sign-up on a shift: its status, organization, costume,
 * or signing up again (the ChangeEventTrooper* and ChangeEventGuest* commands).
 */
enum SignUpChangeOutcome: string
{
    /**
     * The change is saved.
     */
    case SAVED = 'saved';

    /**
     * The person making the change may not make it.
     */
    case FORBIDDEN = 'forbidden';

    /**
     * A trooper picked a status the event doesn't offer them.
     */
    case STATUS_NOT_OFFERED = 'status_not_offered';

    /**
     * Going would put more troopers at their station than it allows.
     */
    case STATION_FULL = 'station_full';

    /**
     * Command staff approved a stand-by trooper who hasn't picked a costume yet.
     */
    case COSTUME_REQUIRED = 'costume_required';

    /**
     * The organization they moved to has no room left on the shift.
     */
    case ORGANIZATION_FULL = 'organization_full';
}
