<?php

declare(strict_types=1);

namespace App\Messages\Costumes\PageData;

use App\Messages\Costumes\Queries\GetCostumeDeletionImpact;
use App\Messages\Costumes\Resources\CostumeClubResource;
use App\Messages\Costumes\Resources\CostumeResource;
use App\Models\Costume;
use App\Models\Organization;
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
            'costume' => new CostumeResource($this->costume),
            'organizations' => CostumeClubResource::collection(
                Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get()
            ),
            'deletion_impact' => $can_delete ? GetCostumeDeletionImpact::call(costume: $this->costume) : null,
            'confirm_delete' => $can_delete && $this->confirm_delete,
        ];
    }
}
