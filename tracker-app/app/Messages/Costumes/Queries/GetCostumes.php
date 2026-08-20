<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Queries;

use App\Models\Costume;
use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Retrieves all costumes.

 * This query message responds with the costumes data, which can be used by frontend clients
 * to display the available costumes and manage costume assignments.
 *
 * @method static Collection call()
 */
final class GetCostumes extends Message
{
    public function __construct(
        private readonly ?array $organization_ids
    ) {}

    /**
     * Retrieves all costumes.

     *
     * @return Collection A collection representing the costumes, including costume IDs and names
     */
    public function handle(): Collection
    {
        $with = ['organization_costumes.organization'];

        if ($this->organization_ids !== null)
        {
            $with['organization_costumes'] = function ($query) {
                $query->whereHas('organization', fn ($q) => $q->whereIn(Organization::ID, $this->organization_ids));
            };
        }

        return Costume::with($with)
            ->orderBy(Costume::NAME)
            ->get();
    }
}
