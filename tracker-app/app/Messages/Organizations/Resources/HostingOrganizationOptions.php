<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

/**
 * Organizations for a "hosted by" filter, in tree order. Each carries its parent, so events
 * hosted above a picked unit can be matched, and its depth within the list, for indenting.
 */
class HostingOrganizationOptions extends ResourceCollection
{
    /**
     * @return array<int, array{id: int, name: string, parent_id: int|null, depth: int}>
     */
    public function toArray(Request $request): array
    {
        $parents = $this->collection->pluck(Organization::PARENT_ID, Organization::ID);

        return $this->collection
            ->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'parent_id' => $organization->parent_id,
                'depth' => $this->depthOf($organization->id, $parents),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int|null>  $parents
     */
    private function depthOf(int $id, Collection $parents): int
    {
        $depth = 0;
        $parent_id = $parents->get($id);

        while ($parent_id !== null && $parents->has($parent_id) && $depth < 10)
        {
            $depth++;
            $parent_id = $parents->get($parent_id);
        }

        return $depth;
    }
}
