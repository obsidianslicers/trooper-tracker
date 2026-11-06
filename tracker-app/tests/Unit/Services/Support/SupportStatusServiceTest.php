<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Support;

use App\Contracts\DonationSource;
use App\Models\TrooperDonation;
use App\Plugins\PluginDonations;
use App\Services\Support\SupportStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SupportStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tracker.support.goal' => 100.0]);
        TrooperDonation::factory()->create([
            TrooperDonation::AMOUNT => 12.5,
            TrooperDonation::CREATED_AT => now()->startOfMonth()->addDays(3),
        ]);
    }

    public function test_calculate_uses_local_donations_without_donation_sources(): void
    {
        $status = app(SupportStatusService::class)->calculate();

        $this->assertFalse($status['uses_donation_sources']);
        $this->assertSame(12.5, $status['current']);
        $this->assertSame(12.5, $status['progress']);
        $this->assertTrue(Cache::has(SupportStatusService::CACHE_KEY));
    }

    public function test_calculate_prefers_the_donation_sources_month_total(): void
    {
        $this->addSource(40.0);

        $status = app(SupportStatusService::class)->calculate();

        $this->assertTrue($status['uses_donation_sources']);
        $this->assertSame(40.0, $status['current']);
        $this->assertSame(40.0, $status['progress']);
    }

    public function test_calculate_does_not_cache_when_a_source_fails(): void
    {
        $this->addSource(null);

        $status = app(SupportStatusService::class)->calculate();

        $this->assertSame(12.5, $status['current']);
        $this->assertFalse(Cache::has(SupportStatusService::CACHE_KEY));
    }

    public function test_calculate_is_empty_without_a_goal(): void
    {
        config(['tracker.support.goal' => 0]);

        $status = app(SupportStatusService::class)->calculate();

        $this->assertSame(0.0, $status['current']);
        $this->assertFalse(Cache::has(SupportStatusService::CACHE_KEY));
    }

    private function addSource(?float $month_total): void
    {
        $source = Mockery::mock(DonationSource::class);
        $source->shouldReceive('monthTotal')->andReturn($month_total);
        $this->app->instance($source::class, $source);
        $this->app->make(PluginDonations::class)->add($source::class);
    }
}
