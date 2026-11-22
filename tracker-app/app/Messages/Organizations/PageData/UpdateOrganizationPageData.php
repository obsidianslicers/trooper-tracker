<?php

declare(strict_types=1);

namespace App\Messages\Organizations\PageData;

use App\Enums\OrganizationType;
use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginClubSynchronizers;
use App\Plugins\PluginSlots;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The organization form: its name and parent, the roster sync choices administrators get on a
 * club, the fields plugins add, and its logo.
 *
 * @method static array call(Actor $actor, Organization $organization)
 */
final class UpdateOrganizationPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly Organization $organization,
    ) {}

    public function handle(PluginClubSynchronizers $synchronizers, PluginSlots $slots): array
    {
        $organization = $this->organization->loadMissing('parent');
        $parent = $organization->parent;

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'synchronizer' => $organization->synchronizer,
                'is_club' => $organization->type === OrganizationType::ORGANIZATION,
                'parent' => $parent === null ? null : [
                    'name' => $parent->name,
                    'label' => __('admin.Parent :type', ['type' => $parent->type->label()]),
                ],
                'logo_url' => $this->logoUrl($organization),
                TrooperStampsResource::TROOPER_STAMPS => new TrooperStampsResource($organization),
            ],
            'synchronizer_options' => $this->synchronizerOptions($synchronizers),
            'plugin_fields' => $slots->render(
                'admin.organizations.update.fields',
                ['organization' => $organization]
            ),
        ];
    }

    /**
     * Only administrators choose a club's roster sync. A sync that is set but no longer offered
     * stays listed so saving doesn't clear it.
     *
     * @return array<int, array{value: string, label: string}>|null
     */
    private function synchronizerOptions(PluginClubSynchronizers $synchronizers): ?array
    {
        if ($this->organization->type !== OrganizationType::ORGANIZATION || !$this->actor->is_administrator)
        {
            return null;
        }

        $options = $synchronizers->options();
        $current = $this->organization->synchronizer;

        if ($current !== null && !isset($options[$current]))
        {
            $options[$current] = __('admin.:name (not available)', ['name' => $current]);
        }

        $choices = [['value' => '', 'label' => __('admin.None')]];

        foreach ($options as $value => $label)
        {
            $choices[] = ['value' => (string) $value, 'label' => $label];
        }

        return $choices;
    }

    private function logoUrl(Organization $organization): string
    {
        $url = map_image_url($organization->image_path_lg, DEFAULT_ORGANIZATION_IMAGE_URL);

        return $organization->image_path_lg === null
            ? $url
            : $url.'?v='.$organization->updated_at?->timestamp;
    }
}
