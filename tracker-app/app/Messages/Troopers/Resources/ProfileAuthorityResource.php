<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Authority tab of a trooper's Command Staff profile (admin/troopers/components/profile/
 * ProfileAuthority): their role, the roles the viewer may give, and the organizations they
 * moderate or get notified for.
 *
 * @mixin Trooper
 */
class ProfileAuthorityResource extends JsonResource
{
    public function __construct(
        Trooper $trooper,
        private readonly bool $may_give_system_owner,
        private readonly TrooperAuthorityOrganizationCollection $organizations,
    ) {
        parent::__construct($trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::MEMBERSHIP_ROLE => $this->membership_role->value,
            'role_options' => collect(MembershipRole::cases())
                ->reject(fn (MembershipRole $role): bool => $role === MembershipRole::SYSTEM_OWNER
                    && !$this->may_give_system_owner)
                ->map(fn (MembershipRole $role): array => ['value' => $role->value, 'label' => $role->label()])
                ->values()
                ->all(),
            'organizations' => $this->organizations,
        ];
    }
}
