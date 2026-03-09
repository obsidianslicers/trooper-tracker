<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetLeaderboardMetricsQuery;
use App\Features\Reports\Queries\GetLeaderboardMetricsQueryHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetLeaderboardMetricsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_leaderboard_array_with_expected_sections(): void
    {
        $subject = new GetLeaderboardMetricsQueryHandler();

        $result = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dominance', $result);
        $this->assertArrayHasKey('diversity', $result);
        $this->assertArrayHasKey('operatives', $result);
    }

    public function test_invoke_dominance_section_returns_collection(): void
    {
        $subject = new GetLeaderboardMetricsQueryHandler();

        $result = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['dominance']);
    }

    public function test_invoke_diversity_section_returns_collection(): void
    {
        $subject = new GetLeaderboardMetricsQueryHandler();

        $result = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['diversity']);
    }

    public function test_invoke_operatives_section_returns_collection(): void
    {
        $subject = new GetLeaderboardMetricsQueryHandler();

        $result = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['operatives']);
    }
}
