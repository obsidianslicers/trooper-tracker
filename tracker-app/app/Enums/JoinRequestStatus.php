<?php

declare(strict_types=1);

namespace App\Enums;

enum JoinRequestStatus: string
{
    use HasEnumHelpers;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DENIED = 'denied';
}
