<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\MembershipStatus;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Database\Seeders\Issues\Fix406;
use Database\Seeders\Issues\Fix407;
use Database\Seeders\Issues\Fix408;
use Database\Seeders\Issues\Fix409;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Seeders\Issues\Concerns\SeedsLegacyTables;
use Tests\TestCase;

/**
 * Runs Fix406–409 together the way they'll run on production, then again, to prove the set
 * converges and is safe to re-run.
 */
class FixSequenceTest extends TestCase
{
    use RefreshDatabase;
    use SeedsLegacyTables;

    private const string IMPORTED_AT = '2026-05-29 08:34:05';

    /** @var array<string, EventTrooper> */
    private array $rows = [];

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLegacyWorld();
        $this->buildScenario();
    }

    public function test_recommended_order_produces_time_correct_credit(): void
    {
        $this->runFixes([Fix408::class, Fix406::class, Fix407::class, Fix409::class]);

        $this->assertTimeCorrectCredit();
    }

    public function test_numeric_order_produces_the_same_credit(): void
    {
        $this->runFixes([Fix406::class, Fix407::class, Fix408::class, Fix409::class]);

        $this->assertTimeCorrectCredit();
    }

    public function test_rerunning_every_fix_makes_no_further_writes(): void
    {
        $order = [Fix408::class, Fix406::class, Fix407::class, Fix409::class];

        $this->runFixes($order);
        $snapshot = $this->snapshot();

        $this->travel(1)->hours();
        $this->runFixes($order);
        $this->runFixes(array_reverse($order));

        $this->assertSame($snapshot, $this->snapshot());
    }

    private function assertTimeCorrectCredit(): void
    {
        $both = ['501st Legion', 'Rebel Legion'];

        $this->assertCredit(['501st Legion'], $this->rows['later_joiner_tt1_uncredited']);
        $this->assertCredit(['501st Legion'], $this->rows['later_joiner_tt1_overcredited']);
        $this->assertCredit(['501st Legion'], $this->rows['later_joiner_tt2_before_join']);
        $this->assertCredit($both, $this->rows['later_joiner_tt2_after_join']);
        $this->assertCredit(['501st Legion'], $this->rows['false_member_tt2']);
        $this->assertCredit(['501st Legion'], $this->rows['false_member_tt1']);
        $this->assertNull($this->rows['unmapped_tt1']->refresh()->costume_organization_ids);
        $this->assertCredit($both, $this->rows['dual_tag_tt1']);
    }

    private function buildScenario(): void
    {
        $this->buildLaterJoiner();
        $this->buildFalseMember();

        $other = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($other, ['p501' => 1]);
        $this->memberAssignmentSince($other, $this->club('501st Legion'), self::IMPORTED_AT);

        $p501 = $this->clubIds('501st Legion');
        $this->rows['unmapped_tt1'] = $this->tt1Row($other, '2018-01-06 10:00:00', 'Other', 4);
        $this->rows['dual_tag_tt1'] = $this->tt1Row($other, '2018-01-13 10:00:00', 'N/A', 5, $p501);
    }

    /** trooper 644: TT1.0 501st member who joined Rebel Legion on 2026-08-02 */
    private function buildLaterJoiner(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'tkid' => "TK{$trooper->id}"]);
        $this->memberAssignmentSince($trooper, $this->club('501st Legion'), self::IMPORTED_AT);
        $this->membershipSince($trooper, $this->club('Rebel Legion'), '2026-08-02 12:00:00');
        $this->memberAssignmentSince($trooper, $this->club('Rebel Legion'), '2026-08-02 12:00:00');

        $both = $this->clubIds('501st Legion', 'Rebel Legion');

        $this->rows['later_joiner_tt1_uncredited'] =
            $this->tt1Row($trooper, '2017-05-06 10:00', 'Handler', 0);
        $this->rows['later_joiner_tt1_overcredited'] =
            $this->tt1Row($trooper, '2019-05-06 10:00', 'Handler', 0, $both);
        $this->rows['later_joiner_tt2_before_join'] =
            $this->attendedWithCredit($trooper, '2026-07-04 10:00', $both);
        $this->rows['later_joiner_tt2_after_join'] =
            $this->attendedWithCredit($trooper, '2026-09-05 10:00', null);
    }

    /** a TT1.0 501st member whose stray Rebel identifier became a (since retired) membership */
    private function buildFalseMember(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $stray = "stray{$trooper->id}";
        $this->legacyTrooper($trooper, [
            'p501' => 1,
            'tkid' => "TK{$trooper->id}",
            'rebelforum' => $stray,
        ]);
        $this->memberAssignmentSince($trooper, $this->club('501st Legion'), self::IMPORTED_AT);

        $rebel = $this->club('Rebel Legion');
        $retired = MembershipStatus::RETIRED;
        $this->membershipSince($trooper, $rebel, self::IMPORTED_AT, $retired, $stray);
        $this->memberAssignmentSince($trooper, $rebel, '2026-06-01 00:00:00');

        $credit = $this->clubIds('Rebel Legion');
        $this->rows['false_member_tt2'] =
            $this->attendedWithCredit($trooper, '2026-07-11 10:00', $credit);
        $this->rows['false_member_tt1'] =
            $this->tt1Row($trooper, '2019-07-13 10:00', 'Stormtrooper', 0, $credit);
    }

    /** @param  array<int, class-string<Seeder>>  $fixes */
    private function runFixes(array $fixes): void
    {
        foreach ($fixes as $fix)
        {
            $subject = new $fix;
            $subject->run(app(MagicBus::class));
        }
    }

    /** @return array<int, array{0: mixed, 1: string}> */
    private function snapshot(): array
    {
        return EventTrooper::query()
            ->orderBy(EventTrooper::ID)
            ->get()
            ->map(fn (EventTrooper $row) => [
                $row->costume_organization_ids,
                (string) $row->updated_at,
            ])
            ->all();
    }
}
