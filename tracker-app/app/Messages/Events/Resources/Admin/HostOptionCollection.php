<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Admin;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

/**
 * The hosting organization picker, indented by depth. Create Event also needs each one's club,
 * which decides how a pasted request message reads; the Overview tab doesn't.
 */
class HostOptionCollection extends ResourceCollection
{
    /**
     * @param  Collection<int, Organization>  $organizations  In the order shown
     */
    public function __construct(Collection $organizations, private readonly bool $with_club = false)
    {
        parent::__construct($organizations);
    }

    /**
     * @return array<int, array{value: int, label: string, club?: string}>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (Organization $organization): array => [
                'value' => $organization->id,
                'label' => str_repeat('— ', max(0, $organization->depth - 1)).$organization->name,
                ...($this->with_club ? ['club' => $organization->getPrimaryClub()->name] : []),
            ])
            ->values()
            ->all();
    }
}
