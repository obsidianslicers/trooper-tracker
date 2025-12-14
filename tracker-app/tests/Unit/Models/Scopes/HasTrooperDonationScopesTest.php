<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\TrooperDonation;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasTrooperDonationScopesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2024, 6, 15, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_scope_by_trooper_filters_donations_for_specific_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();

        $donation1 = TrooperDonation::factory()->for($trooper)->create();
        $donation2 = TrooperDonation::factory()->for($trooper)->create();
        $donation_other = TrooperDonation::factory()->for($other_trooper)->create();

        // Act
        $result = TrooperDonation::byTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($donation1));
        $this->assertTrue($result->contains($donation2));
        $this->assertFalse($result->contains($donation_other));
    }

    public function test_scope_by_trooper_orders_by_created_at_descending(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $donation1 = TrooperDonation::factory()->for($trooper)->create(['created_at' => now()->subDays(3)]);
        $donation2 = TrooperDonation::factory()->for($trooper)->create(['created_at' => now()->subDay()]);
        $donation3 = TrooperDonation::factory()->for($trooper)->create(['created_at' => now()->subDays(2)]);

        // Act
        $result = TrooperDonation::byTrooper($trooper->id)->get();

        // Assert
        $this->assertEquals($donation2->id, $result[0]->id, 'Most recent should be first');
        $this->assertEquals($donation3->id, $result[1]->id);
        $this->assertEquals($donation1->id, $result[2]->id, 'Oldest should be last');
    }

    public function test_scope_for_month_filters_donations_for_current_month(): void
    {
        // Arrange - Current date is 2024-06-15
        $trooper = Trooper::factory()->create();

        $donation_in_june = TrooperDonation::factory()->for($trooper)->create([
            'created_at' => Carbon::create(2024, 6, 10),
        ]);
        $donation_in_may = TrooperDonation::factory()->for($trooper)->create([
            'created_at' => Carbon::create(2024, 5, 15),
        ]);
        $donation_in_july = TrooperDonation::factory()->for($trooper)->create([
            'created_at' => Carbon::create(2024, 7, 1),
        ]);

        // Act
        $result = TrooperDonation::forMonth()->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($donation_in_june));
        $this->assertFalse($result->contains($donation_in_may));
        $this->assertFalse($result->contains($donation_in_july));
    }

    public function test_scope_for_month_filters_donations_for_specific_month(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $donation_in_march = TrooperDonation::factory()->for($trooper)->create([
            'created_at' => Carbon::create(2024, 3, 15),
        ]);
        $donation_in_april = TrooperDonation::factory()->for($trooper)->create([
            'created_at' => Carbon::create(2024, 4, 20),
        ]);

        // Act
        $result = TrooperDonation::forMonth(Carbon::create(2024, 3, 1))->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($donation_in_march));
        $this->assertFalse($result->contains($donation_in_april));
    }

    public function test_scope_for_month_includes_first_and_last_day_of_month(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $donation_first_day = TrooperDonation::factory()->for($trooper)->create([
            'created_at' => Carbon::create(2024, 6, 1, 0, 0, 0),
        ]);
        $donation_last_day = TrooperDonation::factory()->for($trooper)->create([
            'created_at' => Carbon::create(2024, 6, 30, 23, 59, 59),
        ]);
        $donation_before_month = TrooperDonation::factory()->for($trooper)->create([
            'created_at' => Carbon::create(2024, 5, 31, 23, 59, 59),
        ]);

        // Act
        $result = TrooperDonation::forMonth(Carbon::create(2024, 6, 1))->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($donation_first_day));
        $this->assertTrue($result->contains($donation_last_day));
        $this->assertFalse($result->contains($donation_before_month));
    }

    public function test_scope_by_trooper_returns_empty_collection_when_no_donations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $result = TrooperDonation::byTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(0, $result);
    }
}
