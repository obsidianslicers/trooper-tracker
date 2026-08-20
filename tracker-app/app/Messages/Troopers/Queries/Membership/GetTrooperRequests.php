<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Models\Trooper;
use App\Models\TrooperRequest;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Returns the trooper requests for the given trooper
 *
 * @method static Collection call(Trooper $trooper)
 */
final class GetTrooperRequests extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private Carbon|string $since = '30 days ago',
    ) {
        if (is_string($since))
        {
            $this->since = Carbon::parse($since);
        }
    }

    public function handle(): Collection
    {
        $requests = TrooperRequest::query()
            ->with(['organization.parent.parent', 'primary_organization'])
            ->where(TrooperRequest::TROOPER_ID, $this->trooper->id)
            ->where(TrooperRequest::UPDATED_AT, '>=', $this->since)
            ->orderBy(TrooperRequest::CREATED_AT, 'desc')
            ->get();

        $requests->each(function (TrooperRequest $trooper_request) {
            $trooper_request->membership_path = $this->getMembershipPath($trooper_request);
        });

        return $requests;
    }

    private function getMembershipPath(TrooperRequest $trooper_request): string
    {
        $names = [];

        $organization = $trooper_request->organization;

        while ($organization != null)
        {
            $names[] = $organization->name;

            $organization = $organization->parent;
        }

        $names = array_reverse($names);

        return implode(' - ', $names);
    }
}
