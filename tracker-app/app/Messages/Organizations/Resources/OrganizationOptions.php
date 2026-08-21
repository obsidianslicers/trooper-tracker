<?php

namespace App\Messages\Organizations\Resources;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationOptions extends ResourceCollection
{
    /**
     * Summary of __construct
     *
     * @param  mixed  $resource
     * @param  OrganizationType  $organization_type  the lowest level of organization to include (ORGANIZATION > REGION > UNIT)
     */
    public function __construct(
        $resource,
        private readonly OrganizationType $organization_type = OrganizationType::UNIT)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        //  Uses GetOrganizationHierarchy to get the hierarchy, but then flattens it into a
        //  list of options for a select input.
        $options = [];

        $this->collection->each(function (Organization $org) use (&$options) {
            $options[] = ['value' => $org->id, 'label' => $org->name];

            if ($this->organization_type->isRegion() || $this->organization_type->isUnit())
            {
                $org->organizations->each(function ($region) use (&$options) {
                    $options[] = ['value' => $region->id, 'label' => ' — '.$region->name];

                    if ($this->organization_type->isUnit())
                    {
                        $region->organizations->each(function ($unit) use (&$options) {
                            $options[] = ['value' => $unit->id, 'label' => ' — — '.$unit->name];
                        });
                    }
                });
            }
        });

        return $options;
    }
}
