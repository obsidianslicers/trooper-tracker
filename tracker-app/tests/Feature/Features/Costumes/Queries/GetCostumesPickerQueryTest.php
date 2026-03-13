<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Costumes\Queries;

use App\Features\Costumes\Queries\GetCostumesPickerQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetCostumesPickerQueryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_construct_stores_organization_ids(): void
    {
        $organization_ids = [5, 8, 13];

        $subject = new GetCostumesPickerQuery($organization_ids);

        $this->assertSame($organization_ids, $subject->organization_ids);
    }

    public function test_construct_accepts_empty_organization_ids(): void
    {
        $subject = new GetCostumesPickerQuery([]);

        $this->assertSame([], $subject->organization_ids);
    }
}
