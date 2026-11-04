<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetTrooperAwards;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperAwardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_awards_in_lookback_with_expected_relations(): void
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

        $result = (new GetTrooperAwards(30))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($included->id, $result->first()->id);
        $this->assertTrue($result->first()->relationLoaded('award'));
        $this->assertTrue($result->first()->relationLoaded('trooper'));
        $this->assertTrue($result->first()->award->relationLoaded('organization'));
    }

    public function test_handle_orders_results_by_award_date_descending(): void
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

        $result = (new GetTrooperAwards(30))->handle();

        $this->assertGreaterThanOrEqual(
            $result->last()->award_date->timestamp,
            $result->first()->award_date->timestamp,
        );
    }
}
