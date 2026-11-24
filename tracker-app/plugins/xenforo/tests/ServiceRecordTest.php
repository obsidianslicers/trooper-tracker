<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->where('record_slots', function ($slots): bool
            {
                $badges = $slots['service-records.trooper.badges'] ?? '';
                $donations = $slots['service-records.trooper.donations'] ?? '';

                return str_contains($badges, '<span class="userBanner userBanner--primary">Primary</span>')
                    && str_contains($badges, '<span class="userBanner userBanner--reserve">Reserve</span>')
                    && str_contains($slots['service-records.trooper.actions'] ?? '', 'https://xf.test/members/15802/')
                    && str_contains($donations, 'Xenforo Donation History')
                    && str_contains($donations, 'Supporter');
            })
            ->where('summary.donor_image_url', config('tracker.donor.image_url'))
            ->where('summary.total_donated', '$12.50')
            ->where('summary.donation_months', 2));
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->where('record_slots', [])
            ->where('summary.donor_image_url', null));
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
                        'bannerText' => '<span class="userBanner userBanner--reserve">'
                            .'Reserve</span>'],
                    ['title' => 'Primary', 'order' => 10, 'isPrimary' => true,
                        'bannerText' => '<span class="userBanner userBanner--primary">'
                            .'Primary</span>'],
                    ['title' => 'Empty', 'order' => 30, 'isPrimary' => false, 'bannerText' => ''],
                ],
            ]);

            $mock->shouldReceive('get_user_upgrade_history')
                ->once()
                ->with(15802)
                ->andReturn($upgrades);
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
