<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Trooper $trooper)
 */
final class GetTrooperOrganizations extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {}

    public function handle(): Collection
    {
        return TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $this->trooper->id)
            ->where(TrooperOrganization::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->get();
    }
}
