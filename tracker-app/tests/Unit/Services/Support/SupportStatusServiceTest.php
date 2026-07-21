<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Support;

use App\Models\TrooperDonation;
use App\Services\Forums\XenforoService;
use App\Services\Support\SupportStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SupportStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_includes_current_month_expired_xenforo_upgrades(): void
    {
        config([
            'tracker.support.goal' => 100.0,
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $start_date = now()->startOfMonth()->addDay()->timestamp;
        $end_date = now()->startOfMonth()->addDays(2)->timestamp;

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('get_upgrade_stats')
            ->once()
            ->andReturn([
                'userUpgradeActive' => [],
                'userUpgradeExpired' => [
                    [
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'user_upgrade_id' => 2,
                        'extra' => '{"cost_amount":"15.00"}',
                    ],
                ],
                'userUpgrades' => [
                    [
                        'user_upgrade_id' => 2,
                        'cost_amount' => 15.0,
                    ],
                ],
            ]);

        app()->instance(XenforoService::class, $xenforo);

        $subject = app(SupportStatusService::class);
        $status = $subject->calculate();

        $this->assertTrue($status['uses_xenforo']);
        $this->assertSame(100.0, $status['goal']);
        $this->assertSame(15.0, $status['current']);
        $this->assertSame(15.0, $status['progress']);
    }

    public function test_calculate_falls_back_to_local_donations_when_xenforo_total_is_zero(): void
    {
        config([
            'tracker.support.goal' => 100.0,
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        TrooperDonation::factory()->create([
            TrooperDonation::AMOUNT => 12.5,
            TrooperDonation::CREATED_AT => now()->startOfMonth()->addDays(3),
        ]);

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('get_upgrade_stats')
            ->once()
            ->andReturn([
                'userUpgradeActive' => [],
                'userUpgradeExpired' => [],
                'userUpgrades' => [],
            ]);

        app()->instance(XenforoService::class, $xenforo);

        $subject = app(SupportStatusService::class);
        $status = $subject->calculate();

        $this->assertFalse($status['uses_xenforo']);
        $this->assertSame(12.5, $status['current']);
        $this->assertSame(12.5, $status['progress']);
    }

    public function test_calculate_caches_result_and_skips_xenforo_on_second_call(): void
    {
        config([
            'tracker.support.goal' => 100.0,
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('get_upgrade_stats')
            ->once()
            ->andReturn([
                'userUpgradeActive' => [],
                'userUpgradeExpired' => [],
                'userUpgrades' => [],
            ]);

        app()->instance(XenforoService::class, $xenforo);

        $subject = app(SupportStatusService::class);
        $first = $subject->calculate();
        $second = $subject->calculate();

        $this->assertSame($first, $second);
        $this->assertTrue(Cache::has(SupportStatusService::CACHE_KEY));
    }

    public function test_calculate_does_not_cache_when_xenforo_fetch_fails(): void
    {
        config([
            'tracker.support.goal' => 100.0,
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('get_upgrade_stats')
            ->twice()
            ->andReturn(null);

        app()->instance(XenforoService::class, $xenforo);

        $subject = app(SupportStatusService::class);
        $subject->calculate();
        $subject->calculate();

        $this->assertFalse(Cache::has(SupportStatusService::CACHE_KEY));
    }
}
