<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Enums\MembershipRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Membership roles as select options ({value, label}), in the order MembershipRole::toValueLabels
 * gives them. Built from the roles a page offers, e.g. visitor, member and handler on registration.
 */
class MembershipRoleOptions extends ResourceCollection
{
    /**
     * @param  iterable<int, MembershipRole>  $resource
     */
    public function __construct($resource)
    {
        parent::__construct(collect($resource));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toArray(Request $request): array
    {
        //  toValueLabels treats an empty include list as "no filter"
        if ($this->collection->isEmpty())
        {
            return [];
        }

        return MembershipRole::toValueLabels(include: $this->collection->all());
    }
}
