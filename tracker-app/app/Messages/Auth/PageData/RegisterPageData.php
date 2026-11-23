<?php

declare(strict_types=1);

namespace App\Messages\Auth\PageData;

use App\Enums\MembershipRole;
use App\Messages\Auth\Queries\GetAuthConfig;
use App\Messages\Auth\Resources\RegisterOrganizationResource;
use App\Messages\Auth\Resources\RegistrationSessionResource;
use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Messages\Troopers\Resources\MembershipRoleOptions;
use Hyperdrive\Message;

/**
 * The registration page (auth/Register): the email a login provider gave, the clubs with their
 * regions and units, and the account types someone can register as.
 *
 * @method static array<string, mixed> call()
 */
final class RegisterPageData extends Message
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'oauth' => new RegistrationSessionResource(GetAuthConfig::call()),
            'organizations' => RegisterOrganizationResource::collection(GetOrganizationHierarchy::call()),
            'membership_roles' => new MembershipRoleOptions([
                MembershipRole::HANDLER,
                MembershipRole::MEMBER,
                MembershipRole::VISITOR,
            ]),
        ];
    }
}
