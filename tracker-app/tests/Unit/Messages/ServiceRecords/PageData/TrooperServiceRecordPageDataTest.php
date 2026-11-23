<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\PageData\TrooperServiceRecordPageData;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Costume;
use App\Models\Trooper;
use App\Plugins\PluginDonations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class TrooperServiceRecordPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_hides_staff_costumes_and_totals_donations(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->fakeRecord($trooper, missing_credit: 0);

        $subject = new TrooperServiceRecordPageData(trooper: $trooper, actor: $trooper);
        $result = $subject->handle(app(PluginDonations::class));

        $this->assertSame(['Stormtrooper'], $result['trooper_costumes']->pluck('name')->values()->all());
        $this->assertSame(15.0, $result['service_summary']['total_donated']);
        $this->assertSame(1, $result['service_summary']['donation_months']);
        $this->assertFalse($result['is_active_donor']);
        $this->assertFalse($result['has_missing_credit']);
    }

    public function test_handle_flags_missing_credit_for_someone_who_can_fix_it(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $admin = Trooper::factory()->asSystemOwner()->create();
        $this->fakeRecord($trooper, missing_credit: 2);

        $subject = new TrooperServiceRecordPageData(trooper: $trooper, actor: $admin);
        $result = $subject->handle(app(PluginDonations::class));

        $this->assertTrue($result['has_missing_credit']);
    }

    private function fakeRecord(Trooper $trooper, int $missing_credit): void
    {
        $this->fakeMessages([
            GetTrooperServiceRecord::class => [
                'trooper' => $trooper,
                'all_donations' => collect([
                    (object) ['amount' => 10, 'created_at' => now()],
                    (object) ['amount' => 5, 'created_at' => now()],
                ]),
                'service_summary' => [],
            ],
            GetTrooperCostumesForDisplay::class => collect([
                (object) ['name' => 'Stormtrooper'],
                (object) ['name' => Costume::COMMAND_STAFF],
                (object) ['name' => Costume::HANDLER],
            ]),
            GetEventTroopersMissingCredit::class => ['rows' => collect(), 'total' => $missing_credit],
        ]);
    }
}
