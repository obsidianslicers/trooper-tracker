<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Admin;

use App\Messages\Organizations\Queries\GetModeratedOrganizationOptions;
use App\Messages\Troopers\Resources\RecruitIdentifiersResource;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Recruit Trooper form: the units the actor may add troopers to, and for each what its
 * club calls a member's identifier and whether one is required.
 *
 * @method static array call(Actor $actor)
 */
final class RecruitTrooperPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $organizations = Organization::moderatedBy($this->actor)->get();
        $clubs = Organization::whereIn(
            Organization::ID,
            $organizations->map(fn (Organization $organization) => RecruitIdentifiersResource::clubId($organization))->unique()
        )->get()->keyBy(Organization::ID);

        return [
            'organization_options' => GetModeratedOrganizationOptions::call(trooper: $this->actor),
            //  resolved here: Inertia sends a Resource through a JSON response, which would
            //  renumber these organization-id keys
            'identifiers' => (new RecruitIdentifiersResource($organizations, $clubs))->resolve(),
        ];
    }
}
