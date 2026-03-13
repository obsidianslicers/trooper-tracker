<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\OrganizationCostume;
use Tests\TestCase;

class HasOrganizationCostumeScopesTest extends TestCase
{
    public function test_excluding_adds_where_not_in_filter(): void
    {
        $query = OrganizationCostume::query()->excluding([2, 5]);

        $this->assertStringContainsString('"id" not in (?, ?)', $query->toBase()->toSql());
        $this->assertSame([2, 5], $query->getBindings());
    }
}