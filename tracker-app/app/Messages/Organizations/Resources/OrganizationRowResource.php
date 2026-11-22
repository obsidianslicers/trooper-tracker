<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Resources;

use App\Enums\OrganizationType;
use App\Messages\Organizations\Commands\CreateSubOrganization;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Command Staff organization tree. Load it through `withAllAssignments`, which
 * brings the viewer's assignments and whether they moderate it.
 *
 * @mixin Organization
 */
class OrganizationRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Trooper $trooper */
        $trooper = $request->user();
        $can_create = $trooper->can('create', Organization::class);
        $child_type = CreateSubOrganization::childType($this->resource);

        return [
            Organization::ID => $this->id,
            Organization::NAME => $this->name,
            Organization::DEPTH => $this->depth,
            'logo_url' => map_image_url($this->image_path_sm, 'img/icons/organization-32x32.png'),
            'synchronized_at' => $this->type === OrganizationType::ORGANIZATION
                ? $this->synchronized_at?->localFormat('M j, Y')
                : null,
            'can_manage' => $this->canManage($trooper),
            'add_child_label' => $can_create && $child_type !== null
                ? ($child_type === OrganizationType::REGION ? __('admin.Add Region') : __('admin.Add Unit'))
                : null,
            'has_costumes' => $this->type === OrganizationType::ORGANIZATION,
        ];
    }

    private function canManage(Trooper $trooper): bool
    {
        return $trooper->is_administrator
            || $this->trooper_assignments->where('is_moderator', true)->isNotEmpty()
            || (bool) $this->can_moderate;
    }
}
