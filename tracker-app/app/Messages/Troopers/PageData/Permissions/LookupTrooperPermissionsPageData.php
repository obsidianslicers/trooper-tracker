<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Permissions;

use App\Messages\Troopers\Resources\PermissionRequestsCollection;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginGuard;
use App\Plugins\PluginPermissionRequests;
use Hyperdrive\Message;

/**
 * A trooper's current role, moderated organizations, and forum groups, used to
 * pre-fill the permission request form once a trooper is picked.
 *
 * @method static array<string, mixed> call(Trooper $trooper)
 */
final class LookupTrooperPermissionsPageData extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(PluginPermissionRequests $extensions): array
    {
        $moderated_ids = array_map('intval', $this->trooper->resolveModeratorOrgIds() ?? []);

        return [
            'trooper' => [
                'id' => $this->trooper->id,
                'display_name' => $this->trooper->display_name,
                'legal_name' => $this->trooper->legal_name,
                'membership_role' => $this->trooper->membership_role?->value,
                'membership_role_label' => PermissionRequestsCollection::roleLabel(
                    $this->trooper->membership_role
                ),
            ],
            'moderated_organization_ids' => $moderated_ids,
            'covered_organization_ids' => $this->getCoveredOrganizationIds($moderated_ids),
            'extension_sections' => $this->getExtensionSections($extensions),
        ];
    }

    /**
     * @return array<int, array{key:string,html:string}>
     */
    private function getExtensionSections(PluginPermissionRequests $extensions): array
    {
        $sections = [];

        foreach ($extensions->all() as $key => $extension)
        {
            $html = PluginGuard::attempt(
                "show permission request section [{$key}]",
                fn (): string => $extension->formSection($this->trooper),
                '',
            );

            if ($html !== '')
            {
                $sections[] = ['key' => $key, 'html' => $html];
            }
        }

        return $sections;
    }

    /**
     * Moderating an organization covers everything beneath it. The guard keeps
     * moderatedBy() from returning every organization for an administrator.
     *
     * @param  array<int, int>  $moderated_ids
     * @return array<int, int>
     */
    private function getCoveredOrganizationIds(array $moderated_ids): array
    {
        if ($moderated_ids === [])
        {
            return [];
        }

        return Organization::moderatedBy($this->trooper)
            ->pluck(Organization::ID)
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }
}
