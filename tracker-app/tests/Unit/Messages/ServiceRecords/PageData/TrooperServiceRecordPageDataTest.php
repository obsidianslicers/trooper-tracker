<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\PageData\TrooperServiceRecordPageData;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Costume;
use App\Models\Trooper;
use App\Models\TrooperDonation;
use App\Plugins\PluginDonations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class TrooperServiceRecordPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_hides_staff_costumes_and_totals_donations(): void
    {
        config(['tracker.support.url' => 'https://example.com/support']);
        $trooper = Trooper::factory()->asActive()->create();
        $this->fakeRecord($trooper, missing_credit: 0);

        $result = $this->resolve(new TrooperServiceRecordPageData(trooper: $trooper, actor: $trooper));

        $this->assertSame(['Stormtrooper'], array_column($result['costumes'], 'name'));
        $this->assertSame('$15.00', $result['summary']['total_donated']);
        $this->assertSame(1, $result['summary']['donation_months']);
        $this->assertNull($result['summary']['donor_image_url']);
        $this->assertNull($result['trooper']['missing_credit_url']);
        $this->assertNull($result['trooper']['edit_url']);
        $this->assertTrue($result['trooper']['is_self']);
        $this->assertCount(2, $result['donations']);
        $this->assertSame('15.00', $result['donations_total']);
    }

    public function test_handle_leaves_out_donations_when_support_is_off(): void
    {
        config(['tracker.support.url' => null]);
        $trooper = Trooper::factory()->asActive()->create();
        $this->fakeRecord($trooper, missing_credit: 0);

        $result = $this->resolve(new TrooperServiceRecordPageData(trooper: $trooper, actor: $trooper));

        $this->assertNull($result['donations']);
        $this->assertNull($result['donations_total']);
    }

    public function test_handle_links_missing_credit_for_someone_who_can_fix_it(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $admin = Trooper::factory()->asSystemOwner()->create();
        $this->fakeRecord($trooper, missing_credit: 2);

        $result = $this->resolve(new TrooperServiceRecordPageData(trooper: $trooper, actor: $admin));

        $this->assertSame(
            route('admin.service-records.missing-credits.index', ['trooper_id' => $trooper->id]),
            $result['trooper']['missing_credit_url']
        );
        $this->assertFalse($result['trooper']['is_self']);
        $this->assertSame([], $result['pending_shifts']);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve(TrooperServiceRecordPageData $subject): array
    {
        $result = $subject->handle(app(PluginDonations::class));

        return json_decode((string) json_encode(collect($result)->map(
            fn ($value) => $value instanceof JsonResource
                ? $value->resolve(Request::create('/'))
                : $value
        )), true);
    }

    private function fakeRecord(Trooper $trooper, int $missing_credit): void
    {
        $this->fakeMessages([
            GetTrooperServiceRecord::class => [
                'trooper' => $trooper,
                'trooper_organizations' => collect(),
                'club_troop_counts' => collect(),
                'tagged_uploads' => collect(),
                'upcoming_shifts' => collect(),
                'recent_shifts' => collect(),
                'pending_confirmation_shifts' => collect(),
                'awards' => collect(),
                'all_donations' => collect([
                    (new TrooperDonation)->forceFill([TrooperDonation::ID => 1, 'amount' => 10, 'created_at' => now()]),
                    (new TrooperDonation)->forceFill([TrooperDonation::ID => 2, 'amount' => 5, 'created_at' => now()]),
                ]),
                'service_summary' => [
                    'total_shifts' => 0,
                    'total_hours' => 0,
                    'rank' => 1,
                    'direct_funds' => 0,
                    'indirect_funds' => 0,
                    'rank_theme' => 'secondary',
                    'rank_title' => 'Recruit',
                    'milestones' => [],
                ],
            ],
            GetTrooperCostumesForDisplay::class => collect([
                Costume::factory()->withName('Stormtrooper')->make(),
                Costume::factory()->withName(Costume::COMMAND_STAFF)->make(),
                Costume::factory()->withName(Costume::HANDLER)->make(),
            ]),
            GetEventTroopersMissingCredit::class => ['rows' => collect(), 'total' => $missing_credit],
        ]);
    }
}
