<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

class ServiceRecordTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;
    use UsesXenforoPlugin;

    private Trooper $viewer;

    private Trooper $target;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key-1',
            'tracker.support.url' => 'https://support.test',
        ]);

        $this->viewer = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $this->target = Trooper::factory()->asMember()->create();

        $this->fakeMessages([
            GetTrooperServiceRecord::class => $this->serviceRecord(),
            GetTrooperCostumesForDisplay::class => collect(),
            GetEventTroopersMissingCredit::class => ['total' => 0],
        ]);
    }

    public function test_page_shows_forum_banners_profile_link_and_upgrades(): void
    {
        $this->mockForumAccount(upgrades: [[
            'upgrade_title' => 'Supporter',
            'start_date' => strtotime('2025-01-10'),
            'end_date' => strtotime('2025-02-10'),
            'is_active' => true,
            'total_amount' => 12.5,
        ]]);
        $this->enableXenforo();

        $response = $this->actingAs($this->viewer)
            ->get(route('service-records.trooper', ['trooper' => $this->target]));

        $response->assertOk();
        $response->assertSeeInOrder([
            '<span class="userBanner userBanner--primary">Primary</span>',
            '<span class="userBanner userBanner--reserve">Reserve</span>',
        ], false);
        $response->assertSee('https://xf.test/members/15802/', false);
        $response->assertSee('Xenforo Donation History');
        $response->assertSee('Supporter');
        $response->assertViewHas('is_active_donor', true);
        $response->assertViewHas(
            'service_summary',
            fn (array $summary): bool => $summary['total_donated'] === 12.5
                && $summary['donation_months'] === 2
        );
    }

    public function test_page_has_no_forum_details_while_plugin_is_disabled(): void
    {
        $this->mock(XenforoService::class, function (MockInterface $mock): void
        {
            $mock->shouldNotReceive('resolve_user_id_for_trooper');
        });

        $response = $this->actingAs($this->viewer)
            ->get(route('service-records.trooper', ['trooper' => $this->target]));

        $response->assertOk();
        $response->assertDontSee('Forum Profile');
        $response->assertDontSee('Xenforo Donation History');
        $response->assertViewHas('is_active_donor', false);
    }

    /**
     * @param  array<int, array<string, mixed>>  $upgrades
     */
    private function mockForumAccount(array $upgrades): void
    {
        $this->mock(XenforoService::class, function (MockInterface $mock) use ($upgrades): void
        {
            $mock->shouldReceive('resolve_user_id_for_trooper')
                ->once()
                ->with($this->target->id)
                ->andReturn(15802);

            $mock->shouldReceive('get_user_groups')->once()->with(15802)->andReturn([
                'userGroups' => [
                    ['title' => 'Reserve', 'order' => 20, 'isPrimary' => false,
                        'bannerText' => '<span class="userBanner userBanner--reserve">Reserve</span>'],
                    ['title' => 'Primary', 'order' => 10, 'isPrimary' => true,
                        'bannerText' => '<span class="userBanner userBanner--primary">Primary</span>'],
                    ['title' => 'Empty', 'order' => 30, 'isPrimary' => false, 'bannerText' => ''],
                ],
            ]);

            $mock->shouldReceive('get_user_upgrade_history')->once()->with(15802)->andReturn($upgrades);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceRecord(): array
    {
        return [
            'trooper' => $this->target,
            'trooper_organizations' => collect(),
            'club_troop_counts' => collect(),
            'tagged_uploads' => collect(),
            'service_summary' => [
                'total_shifts' => 0,
                'total_hours' => 0,
                'rank' => 0,
                'direct_funds' => 0,
                'indirect_funds' => 0,
                'rank_theme' => 'secondary',
                'rank_title' => 'Recruit',
                'milestones' => [],
            ],
            'upcoming_shifts' => collect(),
            'recent_shifts' => collect(),
            'pending_confirmation_shifts' => collect(),
            'all_donations' => collect(),
            'awards' => collect(),
        ];
    }
}
