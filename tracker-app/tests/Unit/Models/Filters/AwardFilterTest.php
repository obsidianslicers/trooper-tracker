<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Models\Award;
use App\Models\Filters\AwardFilter;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AwardFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_filter_by_organization(): void
    {
        $organization_a = Organization::factory()->create();
        $organization_b = Organization::factory()->create();
        $award_for_org_a = Award::factory()->create([Award::ORGANIZATION_ID => $organization_a->id]);
        Award::factory()->create([Award::ORGANIZATION_ID => $organization_b->id]);

        $request = new Request(['organization_id' => $organization_a->id]);
        $subject = new AwardFilter($request);

        $query = $subject->apply(Award::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($award_for_org_a->id, $query->first()->id);
    }
}
