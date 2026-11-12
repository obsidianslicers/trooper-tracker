<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Ids of the top level organizations (clubs) the moderator may manage memberships in.
 *
 * @method static array<int, int> call(Trooper $moderator)
 */
final class GetModeratedOrganizationIds extends Message
{
    public function __construct(private readonly Trooper $moderator) {}

    /**
     * @return array<int, int>
     */
    public function handle(): array
    {
        return Organization::ofTypeOrganizations()
            ->moderatedBy($this->moderator)
            ->pluck(Organization::ID)
            ->all();
    }
}
