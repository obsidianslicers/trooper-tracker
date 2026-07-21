<?php

declare(strict_types=1);

namespace App\Messages\Auth\PageData;

use App\Enums\MembershipRole;
use App\Messages\Auth\Queries\GetAuthConfig;
use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Session;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 */
final class RegisterPageData extends Message
{
    /**
     * Retrieves application configuration as a nested associative array.
     *
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(): array
    {
        //  oauth should be configuration values + session data for the current registration flow
        $data = [
            'oauth' => GetAuthConfig::call(),
            'organizations' => $this->getOrganizationHierarchy(),
            'membership_roles' => $this->getMembershipRoles(),
        ];

        return $data;
    }

    private function getOrganizationHierarchy(): array
    {
        return GetOrganizationHierarchy::call()
            ->map(fn (Organization $org) => [
                'id' => $org->id,
                'name' => $org->name,
                'identifier_display' => $org->identifier_display,
                'requires_guardian' => $org->requires_guardian,
                'regions' => $org->organizations->map(fn ($region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'units' => $region->organizations->map(fn ($unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                    ]),
                ]),
            ])->toArray();
    }

    private function getMembershipRoles(): array
    {
        $exclude = [MembershipRole::MODERATOR, MembershipRole::ADMINISTRATOR];

        return MembershipRole::toValueLabels($exclude);
    }
}
