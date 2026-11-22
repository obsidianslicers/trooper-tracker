<?php

declare(strict_types=1);

namespace App\Messages\Costumes\PageData;

use App\Messages\Costumes\Resources\CostumeRowResource;
use App\Models\Costume;
use App\Models\Filters\CostumeFilter;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Hyperdrive\Message;

/**
 * The Command Staff costume list: one page of costumes with the clubs that offer each.
 *
 * @method static array call(CostumeFilter $filter, string|null $search_term = null)
 */
final class ListCostumesPageData extends Message
{
    public function __construct(
        private readonly CostumeFilter $filter,
        private readonly ?string $search_term = null,
    ) {}

    public function handle(): array
    {
        $organizations = fn ($q) => $q
            ->wherePivotNull(OrganizationCostume::DELETED_AT)
            ->orderBy(Organization::NAME);

        $costumes = Costume::with(['organizations' => $organizations])
            ->orderBy(Costume::NAME)
            ->filterWith($this->filter)
            ->paginate(15)
            ->withQueryString();

        return [
            'costumes' => CostumeRowResource::collection($costumes),
            'search_term' => $this->search_term ?? '',
        ];
    }
}
