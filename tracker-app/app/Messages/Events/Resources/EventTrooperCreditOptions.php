<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The clubs a roster row can credit, and which of them are ticked.
 *
 * @property Collection<int, Organization> $resource
 */
class EventTrooperCreditOptions extends JsonResource
{
    /**
     * @param  Collection<int, Organization>  $org_options
     * @param  array<int, int>  $credited_ids
     */
    public function __construct(Collection $org_options, private readonly array $credited_ids)
    {
        parent::__construct($org_options);
    }

    /**
     * @return array{org_options:array<int, array{id:int,name:string}>,organization_ids:array<int, int>}
     */
    public function toArray(Request $request): array
    {
        return [
            'org_options' => self::options($this->resource),
            'organization_ids' => array_values($this->credited_ids),
        ];
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @return array<int, array{id:int,name:string}>
     */
    public static function options(Collection $organizations): array
    {
        return $organizations
            ->map(fn (Organization $org): array => ['id' => $org->id, 'name' => $org->name])
            ->values()
            ->all();
    }
}
