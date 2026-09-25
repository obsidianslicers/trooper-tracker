<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Trooper $moderator)
 */
final class GetPendingTrooperRequests extends Message
{
    public function __construct(
        private readonly Trooper $moderator,
    ) {}

    public function handle(): Collection
    {
        $relations = [
            'trooper',
            'organization',
            'primary_organization',
        ];

        return TrooperRequest::with($relations)
            ->pending()
            ->whereHas('trooper', function ($query): void
            {
                $query->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE);
            })
            ->forModerator($this->moderator)
            ->orderBy(TrooperRequest::CREATED_AT)
            ->get();
    }
}
