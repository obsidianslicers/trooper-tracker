<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\EventTypes\Commands;

use App\Enums\AchievementType;
use App\Messages\Reports\Queries\GetLeaderboardMetrics;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_saves_settings_and_keeps_the_slug(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $armor_party = EventType::where(EventType::SLUG, 'armorparty')->firstOrFail();

        $response = $this->actingAs($trooper)
            ->post(route('admin.event-types.update', ['event_type' => $armor_party]), [
                'name' => 'Armor Build Party',
                'description' => 'Workshops. No troop credit.',
                'is_counted' => false,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.event-types.index'));
        $this->assertDatabaseHas('tt_event_types', [
            EventType::ID => $armor_party->id,
            EventType::SLUG => 'armorparty',
            EventType::NAME => 'Armor Build Party',
            EventType::DESCRIPTION => 'Workshops. No troop credit.',
            EventType::IS_COUNTED => false,
        ]);
    }

    /**
     * What the edit page promises: leaderboards change right away, stored troop totals at the
     * nightly recalculation, and milestones already reached are kept.
     */
    public function test_marking_a_type_as_not_counting_reaches_events_that_already_happened(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $armor_party = EventType::where(EventType::SLUG, 'armorparty')->firstOrFail();
        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))
            ->create([Event::TYPE => $armor_party->slug]);
        EventTrooper::factory()->forTrooper($trooper)->asAttended()
            ->forEventShift(EventShift::factory()->forEvent($event)->create())
            ->create();

        $this->artisan('tracker:calculate-trooper-achievements', ['--without-notifications' => true])
            ->assertExitCode(0);
        $this->assertSame(1, $this->troopCount($trooper));
        $this->assertTrue($this->onLeaderboard($trooper));

        $this->actingAs($admin)
            ->post(route('admin.event-types.update', ['event_type' => $armor_party]), [
                'name' => $armor_party->name,
                'description' => $armor_party->description,
                'is_counted' => false,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.event-types.index'));

        $this->assertFalse($this->onLeaderboard($trooper));
        $this->assertSame(1, $this->troopCount($trooper), 'stored totals wait for the nightly run');

        $this->artisan('tracker:calculate-trooper-achievements', ['--without-notifications' => true])
            ->assertExitCode(0);
        $this->assertSame(0, $this->troopCount($trooper));
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
        ]);
    }

    public function test_invoke_allows_keeping_its_own_name(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $charity = EventType::where(EventType::SLUG, 'charity')->firstOrFail();

        $response = $this->actingAs($trooper)
            ->post(route('admin.event-types.update', ['event_type' => $charity]), [
                'name' => 'Charity',
                'is_counted' => true,
                'is_active' => false,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse($charity->fresh()->is_active);
    }

    private function troopCount(Trooper $trooper): int
    {
        return (int) TrooperAchievement::query()
            ->where(TrooperAchievement::TROOPER_ID, $trooper->id)
            ->where(TrooperAchievement::TYPE, AchievementType::TROOPER_SHIFTS->value)
            ->first()
            ?->value;
    }

    private function onLeaderboard(Trooper $trooper): bool
    {
        return (new GetLeaderboardMetrics)->handle()['operatives']->pluck('trooper_id')->contains($trooper->id);
    }
}
