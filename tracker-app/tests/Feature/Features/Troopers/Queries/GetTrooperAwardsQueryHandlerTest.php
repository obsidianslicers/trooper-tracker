<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperAwardsQuery;
use App\Features\Troopers\Queries\GetTrooperAwardsQueryHandler;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperAwardsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_awards_in_lookback_with_expected_relations(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $award = Award::factory()->withOrganization($organization)->create();
        $trooper = Trooper::factory()->asMember()->create();

        $included = AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($trooper)
            ->onDate(Carbon::now()->subDays(5)->toDateString())
            ->create();

        AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($trooper)
            ->onDate(Carbon::now()->subDays(90)->toDateString())
            ->create();

        $subject = new GetTrooperAwardsQueryHandler();

        $result = $subject(new GetTrooperAwardsQuery(30));

        $this->assertCount(1, $result);
        $this->assertSame($included->id, $result->first()->id);
        $this->assertTrue($result->first()->relationLoaded('award'));
        $this->assertTrue($result->first()->relationLoaded('trooper'));
        $this->assertTrue($result->first()->award->relationLoaded('organization'));
    }

    public function test_invoke_orders_results_by_award_date_descending(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $award = Award::factory()->withOrganization($organization)->create();
        $trooper = Trooper::factory()->asMember()->create();

        AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($trooper)
            ->onDate(Carbon::now()->subDays(10)->toDateString())
            ->create();

        AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($trooper)
            ->onDate(Carbon::now()->subDays(2)->toDateString())
            ->create();

        $subject = new GetTrooperAwardsQueryHandler();

        $result = $subject(new GetTrooperAwardsQuery(30));

        $this->assertGreaterThanOrEqual(
            $result->last()->award_date->timestamp,
            $result->first()->award_date->timestamp,
        );
    }
}
