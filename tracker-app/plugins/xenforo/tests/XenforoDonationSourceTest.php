<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;
use TroopTracker\Plugins\Xenforo\Donations\XenforoDonationSource;
use TroopTracker\Plugins\Xenforo\Donations\XenforoProfile;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

class XenforoDonationSourceTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    private MockInterface $xenforo;

    private XenforoDonationSource $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key-1',
        ]);

        $this->xenforo = Mockery::mock(XenforoService::class);
        $this->subject = new XenforoDonationSource(
            $this->xenforo,
            new XenforoProfile($this->xenforo),
        );
    }

    public function test_summaries_price_each_month_at_the_upgrade_cost(): void
    {
        $linked = $this->linkedTrooper(77);
        $unlinked = Trooper::factory()->create();

        $this->xenforo->shouldReceive('get_upgrade_stats')->once()->andReturn([
            'userUpgrades' => [['user_upgrade_id' => 1, 'cost_amount' => 5]],
            'userUpgradeActive' => [],
            'userUpgradeExpired' => [[
                'user_id' => 77,
                'user_upgrade_id' => 1,
                'start_date' => strtotime('2025-01-10'),
                'end_date' => strtotime('2025-03-10'),
            ]],
        ]);

        $summaries = $this->subject->summariesFor([$linked->id, $unlinked->id]);
        // A second chunk in the same run reuses the stats already fetched.
        $this->subject->summariesFor([$unlinked->id]);

        $this->assertSame([$linked->id], array_keys($summaries));
        $this->assertSame(15.0, $summaries[$linked->id]->total);
        $this->assertSame(
            ['2025-01', '2025-02', '2025-03'],
            array_keys($summaries[$linked->id]->month_keys),
        );
        $this->assertFalse($summaries[$linked->id]->is_active);
    }

    public function test_summaries_are_empty_without_api_settings(): void
    {
        config(['plugins.xenforo.api_key' => null]);
        $this->xenforo->shouldNotReceive('get_upgrade_stats');

        $this->assertSame([], $this->subject->summariesFor([$this->linkedTrooper(77)->id]));
    }

    public function test_summary_for_uses_live_upgrade_history(): void
    {
        $trooper = $this->linkedTrooper(77);
        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')->once()->andReturn(77);
        $this->xenforo->shouldReceive('get_user_upgrade_history')->once()->with(77)->andReturn([
            [
                'start_date' => strtotime('2025-05-01'),
                'end_date' => strtotime('2025-06-20'),
                'is_active' => true,
                'total_amount' => 9.5,
            ],
        ]);

        $summary = $this->subject->summaryFor($trooper);

        $this->assertNotNull($summary);
        $this->assertSame(9.5, $summary->total);
        $this->assertSame(['2025-05', '2025-06'], array_keys($summary->month_keys));
        $this->assertTrue($summary->is_active);
    }

    private function linkedTrooper(int $forum_user_id): Trooper
    {
        $trooper = Trooper::factory()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => (string) $forum_user_id,
        ]);

        return $trooper;
    }
}
