<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Commands;

use App\Models\Costume;
use App\Models\OrganizationCostume;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Renames a costume and sets the clubs that offer it. A club taken off keeps its soft-deleted
 * link, which comes back if the club is ticked again.
 *
 * @method static Costume call(Costume $costume, string $name, array $organization_ids)
 */
final class UpdateCostume extends Message
{
    use ShouldBeTransactional;

    /** @param  array<int, int>  $organization_ids */
    public function __construct(
        private readonly Costume $costume,
        private readonly string $name,
        private readonly array $organization_ids,
    ) {}

    public function handle(): Costume
    {
        $this->costume->name = $this->name;
        $this->costume->save();

        $current_ids = OrganizationCostume::query()
            ->where(OrganizationCostume::COSTUME_ID, $this->costume->id)
            ->pluck(OrganizationCostume::ORGANIZATION_ID)
            ->all();

        $this->removeOrganizations(array_diff($current_ids, $this->organization_ids));
        $this->addOrganizations(array_diff($this->organization_ids, $current_ids));

        return $this->costume;
    }

    /** @param  array<int, int>  $organization_ids */
    private function removeOrganizations(array $organization_ids): void
    {
        foreach ($organization_ids as $organization_id)
        {
            OrganizationCostume::query()
                ->where(OrganizationCostume::ORGANIZATION_ID, $organization_id)
                ->where(OrganizationCostume::COSTUME_ID, $this->costume->id)
                ->delete();
        }
    }

    /** @param  array<int, int>  $organization_ids */
    private function addOrganizations(array $organization_ids): void
    {
        foreach ($organization_ids as $organization_id)
        {
            $record = OrganizationCostume::withTrashed()
                ->where(OrganizationCostume::ORGANIZATION_ID, $organization_id)
                ->where(OrganizationCostume::COSTUME_ID, $this->costume->id)
                ->first();

            if ($record === null)
            {
                OrganizationCostume::create([
                    OrganizationCostume::ORGANIZATION_ID => $organization_id,
                    OrganizationCostume::COSTUME_ID => $this->costume->id,
                ]);
            }
            elseif ($record->trashed())
            {
                $record->restore();
            }
        }
    }
}
