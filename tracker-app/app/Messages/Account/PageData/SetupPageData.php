<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Messages\Organizations\Queries\GetOrganizations;
use App\Messages\Troopers\Queries\GetTrooperAssignments;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 *
 * @method static array<string, mixed> call()
 */
final class SetupPageData extends Message
{
    /**
     * Constructs the IndexPageData message.
     *
     * @param  Actor&Trooper  $actor  The actor representing the current user
     */
    public function __construct(
        private readonly Actor $actor
    ) {}

    /**
     * Retrieves application configuration as a nested associative array.
     *
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(): array
    {
        $data = [
            'trooper' => $this->actor,
            'organization_memberships' => $this->getOrganizationMemberships(),
        ];

        return $data;
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
