<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call()
 */
final class GetAdministrators extends Message
{
    public function __construct() {}

    public function handle(): Collection
    {
        return Trooper::active()
            ->where(Trooper::MEMBERSHIP_ROLE, MembershipRole::ADMINISTRATOR)
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();
    }
}
