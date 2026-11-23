<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A trooper's current permissions, to pre-fill the permission request form once they are picked
 * (admin/troopers/RequestPermissions): their role, the organizations they moderate and what that
 * covers, and the plugins' extra form sections.
 *
 * @mixin Trooper
 */
class TrooperPermissionsLookupResource extends JsonResource
{
    /**
     * @param  array<int, int>  $moderated_organization_ids
     * @param  array<int, int>  $covered_organization_ids
     * @param  array<int, array{key: string, html: string}>  $extension_sections
     */
    public function __construct(
        Trooper $trooper,
        private readonly array $moderated_organization_ids,
        private readonly array $covered_organization_ids,
        private readonly array $extension_sections,
    ) {
        parent::__construct($trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'trooper' => [
                Trooper::MEMBERSHIP_ROLE => $this->membership_role?->value,
                'membership_role_label' => PermissionRequestsCollection::roleLabel($this->membership_role),
            ],
            'moderated_organization_ids' => $this->moderated_organization_ids,
            'covered_organization_ids' => $this->covered_organization_ids,
            'extension_sections' => $this->extension_sections,
        ];
    }
}
