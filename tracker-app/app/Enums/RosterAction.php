<?php

declare(strict_types=1);

namespace App\Enums;

enum RosterAction: string
{
    use HasEnumHelpers;

    case SIGNED_UP = 'signed_up';
    case CANCELLED = 'cancelled';
    case RESIGNED_UP = 'resigned_up';
}
