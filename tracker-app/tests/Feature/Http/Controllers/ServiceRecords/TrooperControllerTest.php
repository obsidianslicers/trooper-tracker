<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Contracts\DonationSource;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperDonation;
use App\Plugins\PluginDonations;
use App\Services\BreadCrumbService;
use App\Support\DonationSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class TrooperControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->get(route('service-records.trooper', ['trooper' => $trooper]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_service_record_and_filters_staff_costumes(): void
    {
        $auth_trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $target_trooper = Trooper::factory()->asMember()->create();

        $data = $this->makeServiceRecordData($target_trooper);

        $trooper_costumes = collect([
            Costume::factory()->withName('TK Classic')->make(),
            Costume::factory()->withName(Costume::COMMAND_STAFF)->make(),
            Costume::factory()->withName(Costume::HANDLER)->make(),
        ]);

        $messages = $this->fakeMessages([
            GetTrooperServiceRecord::class => $data,
            GetTrooperCostumesForDisplay::class => $trooper_costumes,
        ]);

        $response = $this->actingAs($auth_trooper)
            ->get(route('service-records.trooper', ['trooper' => $target_trooper]));

        $messages->assertCalled(
            GetTrooperServiceRecord::class,
            fn (array $params): bool => $params['trooper_id'] === $target_trooper->id
        );
        $messages->assertCalled(
            GetTrooperCostumesForDisplay::class,
            fn (array $params): bool => $params['trooper']->id === $target_trooper->id
        );

        $response->assertOk();
        $response->assertViewIs('pages.service-records.trooper');
        $response->assertViewHas('trooper_costumes', function (Collection $result): bool
        {
            return $result->pluck(Costume::NAME)->values()->all() === ['TK Classic'];
        });
    }

    public function test_invoke_adds_profile_breadcrumb_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $this->mock(BreadCrumbService::class, function (MockInterface $mock): void
        {
            $mock->shouldIgnoreMissing();

            $mock->shouldReceive('addRoute')
                ->once()
                ->with('Profile', 'account.index');
        });

        $messages = $this->fakeMessages([
            GetTrooperServiceRecord::class => $this->makeServiceRecordData($trooper),
            GetTrooperCostumesForDisplay::class => collect(),
        ]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.trooper', ['trooper' => $trooper]));

        $messages->assertCalled(
            GetTrooperServiceRecord::class,
            fn (array $params): bool => $params['trooper_id'] === $trooper->id
        );
        $messages->assertCalled(
            GetTrooperCostumesForDisplay::class,
            fn (array $params): bool => $params['trooper']->id === $trooper->id
        );

        $response->assertOk();
    }

    public function test_invoke_adds_donation_source_totals_to_service_summary(): void
    {
        $auth_trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $target_trooper = Trooper::factory()->asMember()->create();
        $data = $this->makeServiceRecordData($target_trooper);
        $data['all_donations'] = collect([
            new TrooperDonation(['amount' => 10, 'created_at' => '2025-01-15']),
        ]);

        $this->fakeMessages([
            GetTrooperServiceRecord::class => $data,
            GetTrooperCostumesForDisplay::class => collect(),
            GetEventTroopersMissingCredit::class => ['total' => 0],
        ]);

        $source = Mockery::mock(DonationSource::class);
        $source->shouldReceive('summaryFor')->once()->andReturn(new DonationSummary(
            month_keys: ['2025-01' => true, '2025-02' => true],
            total: 25.0,
            is_active: true,
        ));
        $this->app->instance($source::class, $source);
        $this->app->make(PluginDonations::class)->add($source::class);

        $response = $this->actingAs($auth_trooper)
            ->get(route('service-records.trooper', ['trooper' => $target_trooper]));

        $response->assertOk();
        $response->assertViewHas('is_active_donor', true);
        $response->assertViewHas('service_summary', fn (array $summary): bool => $summary['total_donated'] === 35.0
            && $summary['donation_months'] === 2);
    }

    public function test_invoke_shows_edit_link_when_authenticated_trooper_can_update_target(): void
    {
        $auth_trooper = Trooper::factory()->asSystemOwner()->withVerifiedEmail()->create();
        $target_trooper = Trooper::factory()->asMember()->create();

        $this->mockServiceRecordQueries($target_trooper);

        $response = $this->actingAs($auth_trooper)
            ->get(route('service-records.trooper', ['trooper' => $target_trooper]));

        $response->assertOk();
        $response->assertSee(route('admin.troopers.profile', ['trooper' => $target_trooper]), false);
        $response->assertSee('Edit');
    }

    public function test_invoke_hides_edit_link_when_authenticated_trooper_cannot_update_target(): void
    {
        $auth_trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $target_trooper = Trooper::factory()->asMember()->create();

        $this->mockServiceRecordQueries($target_trooper);

        $response = $this->actingAs($auth_trooper)
            ->get(route('service-records.trooper', ['trooper' => $target_trooper]));

        $response->assertOk();
        $response->assertDontSee(route('admin.troopers.profile', ['trooper' => $target_trooper]), false);
        $response->assertDontSee('Edit');
    }

    public function test_recent_shift_partial_displays_command_staff_costume_for_handler_pool_assignment(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()->create();
        $command_staff_costume = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->state([
                EventTrooper::COSTUME_ID => $command_staff_costume->id,
                EventTrooper::IS_HANDLER => true,
            ])
            ->create();

        $shift->setRelation('event', $event);
        $event_trooper->setRelation('costume', $command_staff_costume);
        $shift->event_trooper = $event_trooper;

        $response = $this->actingAs($trooper)->view('pages.service-records.inc.trooper-recent-shifts', [
            'trooper' => $trooper,
            'recent_shifts' => collect([$shift]),
            'pending_confirmation_shifts' => collect(),
        ]);

        $response->assertSee(Costume::COMMAND_STAFF);
        $response->assertDontSee('>Handler<', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeServiceRecordData(Trooper $trooper): array
    {
        return [
            'trooper' => $trooper,
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

    private function mockServiceRecordQueries(Trooper $trooper): void
    {
        $data = $this->makeServiceRecordData($trooper);

        $this->fakeMessages([
            GetTrooperServiceRecord::class => function (array $params) use ($trooper, $data): array
            {
                $this->assertSame($trooper->id, $params['trooper_id']);

                return $data;
            },
            GetTrooperCostumesForDisplay::class => function (array $params) use ($trooper): Collection
            {
                $this->assertSame($trooper->id, $params['trooper']->id);

                return collect();
            },
            // Only sent when the authenticated actor can update the target trooper (see
            // TrooperController::__invoke's has_missing_credit flag), so any call count is fine.
            GetEventTroopersMissingCredit::class => function (array $params) use ($trooper): array
            {
                $this->assertSame($trooper->id, $params['trooper_id']);

                return ['rows' => collect(), 'total' => 0];
            },
        ]);
    }
}
