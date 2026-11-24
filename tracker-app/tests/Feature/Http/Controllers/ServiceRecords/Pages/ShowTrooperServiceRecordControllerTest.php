<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords\Pages;

use App\Contracts\DonationSource;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Costume;
use App\Models\Trooper;
use App\Models\TrooperDonation;
use App\Plugins\PluginDonations;
use App\Services\BreadCrumbService;
use App\Support\DonationSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ShowTrooperServiceRecordControllerTest extends TestCase
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('service-records/Trooper')
            ->where('trooper.display_name', $target_trooper->display_name)
            ->has('costumes', 1)
            ->where('costumes.0.name', 'TK Classic'));
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->where('summary.total_donated', '$35.00')
            ->where('summary.donation_months', 2));
    }

    public function test_invoke_shows_edit_link_when_authenticated_trooper_can_update_target(): void
    {
        $auth_trooper = Trooper::factory()->asSystemOwner()->withVerifiedEmail()->create();
        $target_trooper = Trooper::factory()->asMember()->create();

        $this->mockServiceRecordQueries($target_trooper);

        $response = $this->actingAs($auth_trooper)
            ->get(route('service-records.trooper', ['trooper' => $target_trooper]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->where('trooper.edit_url', route('admin.troopers.show', ['trooper' => $target_trooper]))
            ->where('trooper.missing_credit_url', null));
    }

    public function test_invoke_hides_edit_link_when_authenticated_trooper_cannot_update_target(): void
    {
        $auth_trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $target_trooper = Trooper::factory()->asMember()->create();

        $this->mockServiceRecordQueries($target_trooper);

        $response = $this->actingAs($auth_trooper)
            ->get(route('service-records.trooper', ['trooper' => $target_trooper]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->where('trooper.edit_url', null)
            ->where('trooper.missing_credit_url', null));
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
