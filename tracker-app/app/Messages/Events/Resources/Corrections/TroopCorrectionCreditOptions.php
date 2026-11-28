<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Corrections;

use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The costumes a trooper can pick on the Request Troop Credit form, and for each (and for no
 * costume) the top-level clubs it can credit, so the form only offers clubs that fit.
 *
 * @property EventShift $resource
 */
class TroopCorrectionCreditOptions extends JsonResource
{
    public function __construct(EventShift $event_shift, private readonly Trooper $trooper)
    {
        parent::__construct($event_shift);
    }

    /**
     * @return array{costumes:list<array{id:int,name:string,club_ids:list<int>}>,clubs:list<array{id:int,name:string}>,no_costume_club_ids:list<int>}
     */
    public function toArray(Request $request): array
    {
        $clubs = collect();

        $costumes = collect($this->resource->costumeOptionsFor($this->trooper))
            ->map(function (string $name, int $id) use ($clubs): array
            {
                $eligible = $this->clubsFor($id);
                $eligible->each(fn (Organization $club) => $clubs->put($club->id, $club));

                return ['id' => $id, 'name' => $name, 'club_ids' => $eligible->pluck('id')->values()->all()];
            })
            ->values()
            ->all();

        $no_costume = $this->clubsFor(null);
        $no_costume->each(fn (Organization $club) => $clubs->put($club->id, $club));

        return [
            'costumes' => $costumes,
            'clubs' => $clubs
                ->sortBy(Organization::NAME)
                ->map(fn (Organization $club): array => ['id' => $club->id, 'name' => $club->name])
                ->values()
                ->all(),
            'no_costume_club_ids' => $no_costume->pluck('id')->values()->all(),
        ];
    }

    /**
     * @return Collection<int, Organization>
     */
    private function clubsFor(?int $costume_id): Collection
    {
        $event_trooper = new EventTrooper([
            EventTrooper::TROOPER_ID => $this->trooper->id,
            EventTrooper::COSTUME_ID => $costume_id,
        ]);
        $event_trooper->setRelation('trooper', $this->trooper);

        return $event_trooper->getEligibleCreditParentOrganizations();
    }
}
