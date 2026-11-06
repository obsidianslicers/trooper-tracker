<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Tests;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Federation\Messages\Commands\RefreshTrackerLink;
use TroopTracker\Plugins\Federation\Messages\Queries\GetLinkedTroops;
use TroopTracker\Plugins\Federation\Models\LinkedTroop;
use TroopTracker\Plugins\Federation\Models\TrackerLink;

class LinkedTroopsTest extends TestCase
{
    use RefreshDatabase;
    use UsesFederationPlugin;

    private const HISTORY = self::GEORGIA.'/webhooks/federation/history';

    private Trooper $trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeTrackers();
        $this->enableFederation();
        $this->trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
    }

    public function test_refresh_replaces_the_copy_and_drops_malformed_troops(): void
    {
        $link = $this->link();
        $link->troops()->create($this->troop('old', '2025-01-01 10:00', '2025-01-01 11:00'));
        $this->fakeTrackers([self::HISTORY => Http::response(['troops' => [
            ['id' => '1', 'event_name' => 'Parade', 'shift_starts_at' => '2026-02-01T10:00:00Z',
                'shift_ends_at' => '2026-02-01T13:00:00Z', 'clubs' => ['501st Legion'], 'is_handler' => true],
            ['id' => '2', 'event_name' => 'No times'],
            ['id' => '3', 'event_name' => 'Backwards', 'shift_starts_at' => '2026-02-01T10:00:00Z',
                'shift_ends_at' => '2026-02-01T09:00:00Z'],
            'not a troop',
        ]])]);

        $this->assertTrue(RefreshTrackerLink::call(link: $link));

        Http::assertSent(fn (Request $request): bool => $request->url() === self::HISTORY
            && $request->hasHeader('Authorization', 'Bearer access-key'));
        $troop = $link->troops()->sole();
        $this->assertSame('Parade', $troop->event_name);
        $this->assertSame(['501st Legion'], $troop->clubs);
        $this->assertTrue($troop->is_handler);
        $this->assertNotNull($link->fresh()->refreshed_at);
    }

    public function test_a_refused_link_is_marked_and_its_troops_removed(): void
    {
        $link = $this->link();
        $link->troops()->create($this->troop('1', '2025-01-01 10:00', '2025-01-01 11:00'));
        $this->fakeTrackers([self::HISTORY => Http::response(['error' => 'invalid_token'], 401)]);

        $this->assertFalse(RefreshTrackerLink::call(link: $link));

        $this->assertSame(TrackerLink::STATUS_REVOKED, $link->fresh()->status);
        $this->assertSame(0, LinkedTroop::count());
    }

    public function test_an_unreachable_tracker_keeps_the_copy(): void
    {
        $link = $this->link();
        $link->troops()->create($this->troop('1', '2025-01-01 10:00', '2025-01-01 11:00'));
        $this->fakeTrackers([self::HISTORY => fn () => throw new ConnectionException('down')]);

        $this->assertFalse(RefreshTrackerLink::call(link: $link));

        $this->assertSame(1, LinkedTroop::count());
        $this->assertSame(TrackerLink::STATUS_ACTIVE, $link->fresh()->status);
        $this->assertStringContainsString("couldn't be reached", $link->fresh()->last_error);
    }

    public function test_overlapping_troops_count_once(): void
    {
        $this->attendedTroop($this->trooper, '2026-05-01 10:00', '2026-05-01 14:00');
        $georgia = $this->link();
        $texas = $this->link('https://texas.test', 'Texas');
        $georgia->troops()->create($this->troop('a', '2026-05-01 12:00', '2026-05-01 13:00'));
        $georgia->troops()->create($this->troop('b', '2026-06-01 10:00', '2026-06-01 12:00'));
        $texas->troops()->create($this->troop('c', '2026-06-01 11:00', '2026-06-01 13:00'));
        $texas->troops()->create($this->troop('d', '2026-07-01 10:00', '2026-07-01 12:00'));

        $troops = GetLinkedTroops::call(trooper: $this->trooper);

        $this->assertSame(['d', 'b'], $troops->pluck(LinkedTroop::REMOTE_ID)->all());
    }

    public function test_service_record_adds_linked_troops_to_the_totals_and_history(): void
    {
        $link = $this->link();
        $link->troops()->create(array_merge(
            $this->troop('1', '2025-01-01 10:00', '2025-01-01 12:30'),
            [LinkedTroop::EVENT_NAME => 'Georgia hospital visit'],
        ));

        $this->actingAs($this->trooper)
            ->get(route('service-records.trooper', ['trooper' => $this->trooper]))
            ->assertOk()
            ->assertSee('All-Time Troops')
            ->assertSee('Troops from Linked Trackers')
            ->assertSee('Georgia hospital visit')
            ->assertSee('501st Georgia Garrison')
            ->assertSee(route('plugins.federation.index'), false);
    }

    public function test_service_record_has_no_linked_section_without_links(): void
    {
        $other = Trooper::factory()->asMember()->create();

        $this->actingAs($this->trooper)
            ->get(route('service-records.trooper', ['trooper' => $other]))
            ->assertOk()
            ->assertDontSee('All-Time Troops')
            ->assertDontSee(route('plugins.federation.index'), false);
    }

    public function test_unlinking_removes_the_troops_and_tells_the_other_tracker(): void
    {
        $this->fakeTrackers([self::GEORGIA.'/webhooks/federation/revoke' => Http::response(null, 204)]);
        $link = $this->link();
        $link->troops()->create($this->troop('1', '2025-01-01 10:00', '2025-01-01 11:00'));

        $this->actingAs($this->trooper)
            ->delete(route('plugins.federation.links.remove', ['link' => $link]))
            ->assertRedirect(route('plugins.federation.index'));

        $this->assertSame(0, TrackerLink::count());
        $this->assertSame(0, LinkedTroop::count());
        Http::assertSent(fn (Request $request): bool => $request->url() === self::GEORGIA.'/webhooks/federation/revoke'
            && $request->hasHeader('Authorization', 'Bearer access-key'));
    }

    public function test_another_troopers_link_cannot_be_touched(): void
    {
        $link = $this->link();
        $other = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $this->actingAs($other)
            ->delete(route('plugins.federation.links.remove', ['link' => $link]))
            ->assertNotFound();
        $this->actingAs($other)
            ->post(route('plugins.federation.links.refresh', ['link' => $link]))
            ->assertNotFound();

        $this->assertSame(1, TrackerLink::count());
    }

    public function test_refresh_command_refreshes_active_links(): void
    {
        $this->link();
        $this->link('https://texas.test', 'Texas')->update([TrackerLink::STATUS => TrackerLink::STATUS_REVOKED]);
        $this->fakeTrackers([self::HISTORY => Http::response(['troops' => []])]);

        $this->artisan('federation:refresh-links')
            ->expectsOutput('Refreshed 1 links, 0 failed.')
            ->assertSuccessful();
    }

    private function link(string $url = self::GEORGIA, string $name = '501st Georgia Garrison'): TrackerLink
    {
        return TrackerLink::query()->create([
            TrackerLink::TROOPER_ID => $this->trooper->id,
            TrackerLink::TRACKER_URL => $url,
            TrackerLink::TRACKER_NAME => $name,
            TrackerLink::TOKEN => 'access-key',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function troop(string $id, string $starts_at, string $ends_at): array
    {
        return [
            LinkedTroop::REMOTE_ID => $id,
            LinkedTroop::EVENT_NAME => "Troop {$id}",
            LinkedTroop::SHIFT_STARTS_AT => $starts_at,
            LinkedTroop::SHIFT_ENDS_AT => $ends_at,
        ];
    }
}
