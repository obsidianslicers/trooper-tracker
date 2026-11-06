<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Plugins\PluginGuard;
use App\Plugins\PluginPermissionRequests;
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
                'moderated_organizations_to_add' => $organizations
                    ->only($permission_request->moderated_organization_ids_to_add ?? [])
                    ->values()
                    ->all(),
                'moderated_organizations_to_remove' => $organizations
                    ->only($permission_request->moderated_organization_ids_to_remove ?? [])
                    ->values()
                    ->all(),
                'extension_changes' => self::extensionChanges($permission_request),
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
     * What plugins' extensions add to a request, such as forum groups.
     *
     * @return array<int, array{label:string,value:string}>
     */
    public static function extensionChanges(PermissionRequest $request): array
    {
        $changes = [];

        foreach (app(PluginPermissionRequests::class)->all() as $extension)
        {
            $summary = PluginGuard::attempt(
                'summarize a permission request',
                fn (): array => $extension->summary($request),
                [],
            );

            foreach ($summary as $label => $value)
            {
                if ($value !== '')
                {
                    $changes[] = ['label' => $label, 'value' => $value];
                }
            }
        }

        return $changes;
    }

    /**
     * Human-readable lines describing what a request changes, for notifications.
     *
     * @return array<int, string>
     */
    public static function changeSummary(PermissionRequest $request): array
    {
        $organizations = Organization::query()
            ->whereIn(Organization::ID, self::organizationIds($request))
            ->pluck(Organization::NAME, Organization::ID);

        $names = fn (?array $ids): string => $organizations->only($ids ?? [])->implode(', ');

        $lines = collect([
            'Role' => (string) self::roleLabel($request->membership_role),
            'Now moderates' => $names($request->moderated_organization_ids_to_add),
            'No longer moderates' => $names($request->moderated_organization_ids_to_remove),
        ])
            ->filter(fn (string $value): bool => $value !== '')
            ->map(fn (string $value, string $label): string => "{$label}: {$value}")
            ->values();

        foreach (self::extensionChanges($request) as $change)
        {
            $lines->push("{$change['label']}: {$change['value']}");
        }

        return $lines->all();
    }

    /**
     * Every organization a request adds or removes moderation for.
     *
     * @return array<int, int>
     */
    private static function organizationIds(PermissionRequest $request): array
    {
        return [
            ...$request->moderated_organization_ids_to_add ?? [],
            ...$request->moderated_organization_ids_to_remove ?? [],
        ];
    }

    /**
     * @return Collection<int, array{id:int,name:string,parent_name:string|null}>
     */
    private function getOrganizations(): Collection
    {
        $ids = $this->collection
            ->flatMap(fn (PermissionRequest $r): array => self::organizationIds($r))
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
