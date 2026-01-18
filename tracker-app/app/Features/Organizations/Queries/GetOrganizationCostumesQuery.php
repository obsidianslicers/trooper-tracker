<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;


readonly class GetOrganizationCostumesQuery
{
    public function __construct(public readonly ?iterable $organization_ids = null)
    {
    }
}