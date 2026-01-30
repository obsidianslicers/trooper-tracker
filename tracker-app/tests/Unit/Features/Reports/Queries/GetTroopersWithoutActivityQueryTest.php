<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Reports\Queries;

use App\Features\Reports\Queries\GetTroopersWithoutActivityQuery;
use App\Models\Trooper;
use Carbon\Carbon;
use Tests\TestCase;

class GetTroopersWithoutActivityQueryTest extends TestCase
{
    public function test_construct_with_int_lookback(): void
    {
        // Arrange
        $moderator = Trooper::factory()->make();
        $lookback = 30;

        // Act
        $subject = new GetTroopersWithoutActivityQuery($moderator, $lookback);

        // Assert
        $this->assertInstanceOf(GetTroopersWithoutActivityQuery::class, $subject);
        $this->assertSame($moderator, $subject->moderator);
        $this->assertSame($lookback, $subject->lookback);
    }

    public function test_construct_with_string_lookback(): void
    {
        // Arrange
        $moderator = Trooper::factory()->make();
        $lookback = '2025-01-01';

        // Act
        $subject = new GetTroopersWithoutActivityQuery($moderator, $lookback);

        // Assert
        $this->assertInstanceOf(GetTroopersWithoutActivityQuery::class, $subject);
        $this->assertSame($lookback, $subject->lookback);
    }

    public function test_construct_with_carbon_lookback(): void
    {
        // Arrange
        $moderator = Trooper::factory()->make();
        $lookback = Carbon::parse('2025-01-01');

        // Act
        $subject = new GetTroopersWithoutActivityQuery($moderator, $lookback);

        // Assert
        $this->assertInstanceOf(GetTroopersWithoutActivityQuery::class, $subject);
        $this->assertSame($lookback, $subject->lookback);
    }

    public function test_parse_lookback_with_int(): void
    {
        // Arrange
        $moderator = Trooper::factory()->make();
        $subject = new GetTroopersWithoutActivityQuery($moderator, 30);

        // Act
        $result = $subject->parseLookback();

        // Assert
        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals(now()->subDays(30)->format('Y-m-d'), $result->format('Y-m-d'));
    }

    public function test_parse_lookback_with_string(): void
    {
        // Arrange
        $moderator = Trooper::factory()->make();
        $subject = new GetTroopersWithoutActivityQuery($moderator, '2025-01-01');

        // Act
        $result = $subject->parseLookback();

        // Assert
        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2025-01-01', $result->format('Y-m-d'));
    }

    public function test_parse_lookback_with_carbon(): void
    {
        // Arrange
        $moderator = Trooper::factory()->make();
        $lookback = Carbon::parse('2025-01-01');
        $subject = new GetTroopersWithoutActivityQuery($moderator, $lookback);

        // Act
        $result = $subject->parseLookback();

        // Assert
        $this->assertSame($lookback, $result);
    }
}
