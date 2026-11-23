<?php

declare(strict_types=1);

namespace App\Messages\Organizations\PageData;

use App\Enums\OrganizationType;
use App\Messages\Organizations\Resources\OrganizationResource;
use App\Messages\Organizations\Resources\RosterSyncOptions;
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

        return [
            'organization' => new OrganizationResource($organization),
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
     */
    private function synchronizerOptions(PluginClubSynchronizers $synchronizers): ?RosterSyncOptions
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

        return new RosterSyncOptions($options);
    }
}
