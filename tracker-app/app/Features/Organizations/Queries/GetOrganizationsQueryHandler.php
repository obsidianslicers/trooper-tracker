<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;


readonly class GetOrganizationsQueryHandler implements QueryHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        /** @var GetOrganizationsQuery $message */
        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        return $organizations;
    }
}