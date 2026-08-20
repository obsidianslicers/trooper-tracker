<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Queries;

use App\Messages\Troopers\Queries\TrooperMembership\GetTrooperOrganizations;
use App\Models\Costume;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Trooper|null $trooper, string $search_term)
 */
final class SearchCostumes extends Message
{
    /**
     * Summary of __construct
     *
     */
    public function __construct(
        private readonly string $search_term,
        private readonly Trooper|null $trooper, )
    {
    }

    public function handle(): Collection
    {
        $search_term = trim($this->search_term);

        $query = Costume::query()
            ->where(Costume::NAME, 'like', "%{$search_term}%");

        if ($this->trooper)
        {
            $organization_ids = GetTrooperOrganizations::call($this->trooper)
                ->pluck(TrooperOrganization::ORGANIZATION_ID)
                ->all();

            $query->whereHas('organization_costumes', function ($q) use ($organization_ids)
            {
                $q->whereIn('organization_id', $organization_ids);
            });

            $query->with([
                'organization_costumes' => fn($q) => $q->whereIn('organization_id', $organization_ids),
                'organization_costumes.organization',
            ]);
        }
        else
        {
            $query->with(['organization_costumes.organization']);
        }

        return $query->orderBy(Costume::NAME)->get();
    }
}
