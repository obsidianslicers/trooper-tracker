<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Messages\Account\Resources\SetupMembershipResource;
use App\Messages\Account\Resources\SetupTrooperResource;
use App\Messages\Organizations\Queries\GetOrganizations;
use App\Messages\Troopers\Queries\GetTrooperAssignments;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The first-time setup form: the trooper's details so far and the clubs they already belong to.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class SetupPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(
        private readonly Actor $actor
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'trooper' => new SetupTrooperResource($this->actor),
            'memberships' => SetupMembershipResource::collection(
                $this->getOrganizationMemberships()->whereNotNull('assignment')->values()
            ),
        ];
    }

    private function getOrganizationMemberships()
    {
        $organizations = $this->getOrganizationTrooperAssignments();

        $organization_memberships = $organizations->reject(fn ($org) => $org->name === 'Galactic Academy');

        return $organization_memberships;
    }

    private function getOrganizationTrooperAssignments()
    {
        $trooper_assignments = GetTrooperAssignments::call(
            trooper: $this->actor,
            member_only: true
        );

        $organizations = GetOrganizations::call();

        foreach ($organizations as $organization)
        {
            foreach ($trooper_assignments as $assignment)
            {
                if (str_starts_with($assignment->organization->node_path, $organization->node_path))
                {
                    $organization->assignment = $assignment->organization;
                }
            }
        }

        return $organizations;
    }
}
