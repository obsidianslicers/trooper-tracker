<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Tests;

use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;
use TroopTracker\Plugins\Holonet\Messages\Commands\CreateLinkCode;
use TroopTracker\Plugins\Holonet\Models\HolonetGrant;
use TroopTracker\Plugins\Holonet\Support\Pkce;
use TroopTracker\Plugins\Holonet\Support\TroopRecord;

class SharingEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use UsesHolonetPlugin;

    private Trooper $trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeTrackers();
        $this->enableHolonet();
        $this->trooper = Trooper::factory()->asMember()->create();
    }

    public function test_a_code_is_swapped_once_for_an_access_key(): void
    {
        $code = $this->code('verifier-'.str_repeat('x', 50));

        $response = $this->postJson('/webhooks/holonet/token', [
            'code' => $code,
            'code_verifier' => 'verifier-'.str_repeat('x', 50),
            'client' => self::GEORGIA,
        ]);

        $response->assertOk()
            ->assertJsonPath('tracker.url', 'https://florida.test')
            ->assertJsonPath('trooper.display_name', $this->trooper->display_name);
        $grant = HolonetGrant::sole();
        $this->assertSame(hash('sha256', $response->json('token')), $grant->token_hash);
        $this->assertSame(self::GEORGIA, $grant->tracker_url);

        $this->postJson('/webhooks/holonet/token', [
            'code' => $code,
            'code_verifier' => 'verifier-'.str_repeat('x', 50),
            'client' => self::GEORGIA,
        ])->assertStatus(400);
    }

    public function test_a_code_needs_the_right_verifier_and_tracker(): void
    {
        $verifier = str_repeat('v', 64);

        $this->postJson('/webhooks/holonet/token', [
            'code' => $this->code($verifier),
            'code_verifier' => str_repeat('w', 64),
            'client' => self::GEORGIA,
        ])->assertStatus(400);

        $this->postJson('/webhooks/holonet/token', [
            'code' => $this->code($verifier),
            'code_verifier' => $verifier,
            'client' => 'https://florida.test',
        ])->assertStatus(400);

        $this->assertSame(0, HolonetGrant::count());
    }

    public function test_an_expired_code_is_refused(): void
    {
        $verifier = str_repeat('v', 64);
        $code = $this->code($verifier);
        $this->travel(CreateLinkCode::LIFETIME_MINUTES + 1)->minutes();

        $this->postJson('/webhooks/holonet/token', [
            'code' => $code,
            'code_verifier' => $verifier,
            'client' => self::GEORGIA,
        ])->assertStatus(400);
    }

    public function test_history_shares_attended_troops_of_closed_events_only(): void
    {
        $club = Organization::factory()->create([Organization::NAME => '501st Legion']);
        $region = Organization::factory()->create([Organization::PARENT_ID => $club->id]);
        $costume = Costume::factory()->create([Costume::NAME => 'TK - Stormtrooper']);
        $troop = $this->attendedTroop($this->trooper, '2026-03-01 10:00', '2026-03-01 12:00', [
            Event::NAME => 'Library day',
            Event::VENUE => 'Main library',
        ]);
        $troop->update([
            EventTrooper::COSTUME_ID => $costume->id,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$region->id],
        ]);
        $this->attendedTroop($this->trooper, '2026-03-02 10:00', '2026-03-02 12:00')
            ->update([EventTrooper::STATUS => EventTrooperStatus::CANCELLED]);
        $open_event = Event::factory()->create();
        EventTrooper::factory()->asAttended()->forTrooper($this->trooper)
            ->forEventShift(EventShift::factory()->forEvent($open_event)->create())->create();

        $response = $this->withToken($this->grant())->postJson('/webhooks/holonet/history');

        $response->assertOk()
            ->assertJsonPath('version', 1)
            ->assertJsonCount(1, 'troops')
            ->assertJsonPath('troops.0.id', (string) $troop->id)
            ->assertJsonPath('troops.0.event_name', 'Library day')
            ->assertJsonPath('troops.0.venue', 'Main library')
            ->assertJsonPath('troops.0.costume', 'TK - Stormtrooper')
            ->assertJsonPath('troops.0.clubs', ['501st Legion'])
            ->assertJsonMissingPath('troops.0.trooper_id');
        $this->assertNotNull(HolonetGrant::sole()->last_used_at);
        $this->assertNotNull(TroopRecord::toAttributes($response->json('troops.0')));
    }

    public function test_history_needs_a_valid_key(): void
    {
        $this->withToken('nope')->postJson('/webhooks/holonet/history')->assertStatus(401);
        $this->postJson('/webhooks/holonet/history')->assertStatus(401);
    }

    public function test_history_is_refused_once_the_trooper_is_deleted(): void
    {
        $token = $this->grant();
        $this->trooper->delete();

        $this->withToken($token)->postJson('/webhooks/holonet/history')->assertStatus(401);
    }

    public function test_history_is_not_shared_while_sharing_is_off(): void
    {
        $token = $this->grant();
        $this->app->make(PluginManager::class)->updateSettings('holonet', ['share_history' => false]);

        $this->withToken($token)->postJson('/webhooks/holonet/history')->assertStatus(403);
    }

    public function test_revoke_forgets_the_key(): void
    {
        $token = $this->grant();

        $this->withToken($token)->postJson('/webhooks/holonet/revoke')->assertNoContent();

        $this->assertSame(0, HolonetGrant::count());
        $this->withToken($token)->postJson('/webhooks/holonet/history')->assertStatus(401);
    }

    public function test_the_trooper_can_remove_a_trackers_access(): void
    {
        $this->grant();
        $grant = HolonetGrant::sole();
        $other = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $this->actingAs($other)
            ->delete(route('plugins.holonet.grants.revoke', ['grant' => $grant]))
            ->assertNotFound();

        $this->actingAs(tap($this->trooper)->update([Trooper::EMAIL_VERIFIED_AT => now()]))
            ->delete(route('plugins.holonet.grants.revoke', ['grant' => $grant]))
            ->assertRedirect(route('plugins.holonet.index'));

        $this->assertSame(0, HolonetGrant::count());
    }

    private function code(string $verifier): string
    {
        return CreateLinkCode::call(
            trooper: $this->trooper,
            tracker: $this->app->make(TrackerDirectory::class)->find(self::GEORGIA),
            code_challenge: Pkce::challenge($verifier),
        );
    }

    private function grant(): string
    {
        $verifier = str_repeat('v', 64);

        return $this->postJson('/webhooks/holonet/token', [
            'code' => $this->code($verifier),
            'code_verifier' => $verifier,
            'client' => self::GEORGIA,
        ])->json('token');
    }
}
