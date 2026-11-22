<?php

declare(strict_types=1);

namespace App\Messages\Costumes\PageData;

use App\Messages\Costumes\Queries\GetCostumeDeletionImpact;
use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Hyperdrive\Message;

/**
 * The costume form: its name, every club with whether it offers the costume, and what
 * deleting it would change.
 *
 * @method static array call(Costume $costume, bool $confirm_delete = false)
 */
final class UpdateCostumePageData extends Message
{
    public function __construct(
        private readonly Costume $costume,
        private readonly bool $confirm_delete = false,
    ) {}

    public function handle(): array
    {
        $can_delete = !$this->costume->countsAsHandler();

        return [
            'costume' => [
                'id' => $this->costume->id,
                'name' => $this->costume->name,
                'organization_ids' => OrganizationCostume::query()
                    ->where(OrganizationCostume::COSTUME_ID, $this->costume->id)
                    ->pluck(OrganizationCostume::ORGANIZATION_ID),
                'can_delete' => $can_delete,
                TrooperStampsResource::TROOPER_STAMPS => new TrooperStampsResource($this->costume),
            ],
            'organizations' => Organization::ofTypeOrganizations()
                ->orderBy(Organization::NAME)
                ->get([Organization::ID, Organization::NAME]),
            'deletion_impact' => $can_delete ? GetCostumeDeletionImpact::call(costume: $this->costume) : null,
            'confirm_delete' => $can_delete && $this->confirm_delete,
        ];
    }
}
