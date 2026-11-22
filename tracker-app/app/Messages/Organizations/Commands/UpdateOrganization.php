<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Commands;

use App\Models\Organization;
use App\Plugins\PluginForms;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Renames an organization, sets its roster sync when the caller may choose one, and saves the
 * fields plugins add to the form.
 *
 * @method static Organization call(Organization $organization, string $name, array $validated, bool $sets_synchronizer = false, string|null $synchronizer = null)
 */
final class UpdateOrganization extends Message
{
    use ShouldBeTransactional;

    /** @param  array<string, mixed>  $validated  everything the form validated, for the plugins */
    public function __construct(
        private readonly Organization $organization,
        private readonly string $name,
        private readonly array $validated,
        private readonly bool $sets_synchronizer = false,
        private readonly ?string $synchronizer = null,
    ) {}

    public function handle(PluginForms $plugin_forms): Organization
    {
        $this->organization->name = $this->name;

        if ($this->sets_synchronizer)
        {
            $this->organization->synchronizer = $this->synchronizer ?: null;
        }

        $this->organization->save();

        $plugin_forms->saved(PluginForms::ORGANIZATION_UPDATE, $this->organization, $this->validated);

        return $this->organization;
    }
}
