<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class PermissionRequestsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $organizations = $this->getOrganizations();

        return $this->collection
            ->map(fn (PermissionRequest $permission_request): array => [
                'id' => $permission_request->id,
                'status' => $permission_request->status->value,
                'membership_role' => $permission_request->membership_role?->value,
                'membership_role_label' => self::roleLabel($permission_request->membership_role),
                'moderated_organizations' => $organizations
                    ->only($permission_request->moderated_organization_ids ?? [])
                    ->values()
                    ->all(),
                'forum_groups_to_add' => $permission_request->forum_groups_to_add ?? [],
                'forum_groups_to_remove' => $permission_request->forum_groups_to_remove ?? [],
                'note' => $permission_request->note,
                'denial_reason' => $permission_request->denial_reason,
                'created' => $permission_request->created_at?->diffForHumans(),
                'reviewed' => $permission_request->reviewed_at?->diffForHumans(),
                'trooper' => $this->getTrooper($permission_request->trooper),
                'requester' => $this->getTrooper($permission_request->requester),
                'reviewer' => $this->getTrooper($permission_request->reviewer),
            ])->toArray();
    }

    public static function roleLabel(?MembershipRole $role): ?string
    {
        return match ($role)
        {
            null => null,
            MembershipRole::MEMBER => 'Regular Trooper',
            default => (string) to_title($role->name),
        };
    }

    /**
     * @return Collection<int, array{id:int,name:string,parent_name:string|null}>
     */
    private function getOrganizations(): Collection
    {
        $ids = $this->collection
            ->flatMap(fn (PermissionRequest $r): array => $r->moderated_organization_ids ?? [])
            ->unique()
            ->values();

        if ($ids->isEmpty())
        {
            return collect();
        }

        return Organization::with('parent')
            ->whereIn(Organization::ID, $ids)
            ->get()
            ->mapWithKeys(fn (Organization $organization): array => [
                $organization->id => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'parent_name' => $organization->parent?->name,
                ],
            ]);
    }

    private function getTrooper(?Trooper $trooper): ?array
    {
        if ($trooper === null)
        {
            return null;
        }

        return [
            'id' => $trooper->id,
            'display_name' => $trooper->display_name,
            'legal_name' => $trooper->legal_name,
            'membership_role' => $trooper->membership_role?->value,
        ];
    }
}
