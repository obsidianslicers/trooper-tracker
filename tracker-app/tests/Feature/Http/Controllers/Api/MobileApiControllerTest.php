<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\OauthProvider;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_forum_returns_mobile_api_key_for_linked_active_trooper(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.me_path' => '/api/me',
        ]);

        $trooper = Trooper::factory()->asActive()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '12345',
        ]);

        Http::fake([
            'https://xf.test/api/me' => Http::response([
                'me' => [
                    'user_id' => 12345,
                    'username' => 'TK-12345',
                    'avatar_urls' => ['s' => 'https://img.test/avatar-sm.png'],
                ],
            ], 200),
        ]);

        $response = $this->post(route('api.mobile'), [
            'action' => 'login_with_forum',
            'access_token' => 'forum-access-token',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('user.user_id', '12345');
        $response->assertJsonPath('user.username', 'TK-12345');

        $api_key = $response->json('apiKey');

        // API key is now the XenForo OAuth access token; we just
        // ensure it is returned as a non-empty string and that the
        // OAuth login record was stored correctly.
        $this->assertIsString($api_key);
        $this->assertNotSame('', $api_key);

        $this->assertDatabaseHas('tt_oauth_logins', [
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '12345',
            OauthLogin::TOKEN => 'forum-access-token',
        ]);
    }

    public function test_login_with_forum_returns_unauthorized_when_forum_token_is_invalid(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.me_path' => '/api/me',
        ]);

        Http::fake([
            'https://xf.test/api/me' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $response = $this->post(route('api.mobile'), [
            'action' => 'login_with_forum',
            'access_token' => 'bad-token',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Unable to authenticate with the forum: Unauthorized');
    }

    public function test_login_with_forum_returns_not_found_for_unlinked_forum_account(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.me_path' => '/api/me',
        ]);

        Http::fake([
            'https://xf.test/api/me' => Http::response([
                'me' => [
                    'user_id' => 99999,
                    'username' => 'Unlinked Trooper',
                ],
            ], 200),
        ]);

        $response = $this->post(route('api.mobile'), [
            'action' => 'login_with_forum',
            'access_token' => 'forum-access-token',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath(
            'error',
            'No Troop Tracker account is linked to this forum account.',
        );
    }

    public function test_login_with_forum_rejects_inactive_trooper(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.me_path' => '/api/me',
        ]);

        $trooper = Trooper::factory()->asRetired()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '12345',
        ]);

        Http::fake([
            'https://xf.test/api/me' => Http::response([
                'me' => [
                    'user_id' => 12345,
                    'username' => 'Retired Trooper',
                ],
            ], 200),
        ]);

        $response = $this->post(route('api.mobile'), [
            'action' => 'login_with_forum',
            'access_token' => 'forum-access-token',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Trooper account is inactive.');
    }

    public function test_get_roster_for_event_returns_trooper_identifier_from_organization_pivot(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->create([
            Trooper::DISPLAY_NAME => 'TK Test',
        ]);
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();
        $signedUpAt = Carbon::create(2026, 3, 22, 19, 30, 0);

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::GOING,
                EventTrooper::SIGNED_UP_AT => $signedUpAt,
            ]);

        $response = $this->get(route('api.mobile', [
            'action' => 'get_roster_for_event',
            'troopid' => $event->id,
        ]));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.trooper_name', 'TK Test');
        $response->assertJsonPath('0.tkid', 'TK-12345');
        $response->assertJsonPath('0.tkid_formatted', 'TK-12345');
        $response->assertJsonPath('0.squad', $organization->id);
        $response->assertJsonPath('0.signuptime', '2026-03-22 19:30:00');
        $response->assertJsonPath('0.status', EventTrooperStatus::GOING->value);
    }

    public function test_get_roster_for_event_includes_guests(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventGuest::factory()
            ->forEventShift($shift)
            ->create([
                EventGuest::NAME => 'Leia Organa',
                EventGuest::STATUS => EventGuestStatus::GOING,
                EventGuest::SIGNED_UP_AT => Carbon::create(2026, 3, 22, 20, 0, 0),
            ]);

        $response = $this->get(route('api.mobile', [
            'action' => 'get_roster_for_event',
            'troopid' => $event->id,
        ]));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.trooper_name', 'Leia Organa');
        $response->assertJsonPath('0.tkid_formatted', 'Guest');
        $response->assertJsonPath('0.status', EventGuestStatus::GOING->value);
        $response->assertJsonPath('0.status_formatted', 'Going');
        $response->assertJsonPath('0.signuptime', '2026-03-22 20:00:00');
    }

    public function test_get_event_returns_legacy_mobile_payload_shape(): void
    {
        $event = Event::factory()->create([
            Event::NAME => 'Airport Expo',
            Event::VENUE => 'Convention Center',
            Event::VENUE_ADDRESS => '123 Main St',
            Event::VENUE_CITY => 'Orlando',
            Event::VENUE_STATE => 'FL',
            Event::VENUE_ZIP => '32801',
            Event::VENUE_COUNTRY => 'USA',
            Event::EVENT_START => Carbon::create(2026, 4, 10, 9, 0, 0),
            Event::EVENT_END => Carbon::create(2026, 4, 10, 13, 30, 0),
            Event::EVENT_WEBSITE => 'https://example.test/event',
            Event::EXPECTED_ATTENDEES => 250,
            Event::REQUESTED_NUMBER_CHARACTERS => 12,
            Event::REQUESTED_CHARACTER_TYPES => 'Stormtroopers',
            Event::SECURE_STAGING_AREA => true,
            Event::ALLOW_BLASTERS => true,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => true,
            Event::ACCESSIBLE => true,
            Event::AMENITIES => 'Restrooms and water',
            Event::REFERRED_BY => 'Command Staff',
            Event::COMMENTS => 'Report to staging 30 minutes early.',
            Event::TROOPERS_ALLOWED => 10,
            Event::HANDLERS_ALLOWED => 2,
            Event::TENTATIVE_SIGNUPS_ALLOWED => true,
        ]);

        $response = $this->get(route('api.mobile', [
            'action' => 'event',
            'troopid' => $event->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('name', 'Airport Expo');
        $response->assertJsonPath('venue', 'Convention Center');
        $response->assertJsonPath('location', '123 Main St, Orlando, FL, 32801, USA');
        $response->assertJsonPath('dateStart', '2026-04-10 09:00:00');
        $response->assertJsonPath('dateEnd', '2026-04-10 13:30:00');
        $response->assertJsonPath('website', 'https://example.test/event');
        $response->assertJsonPath('comments', 'Report to staging 30 minutes early.');
        $response->assertJsonPath('numberOfAttend', 250);
        $response->assertJsonPath('requestedNumber', 12);
        $response->assertJsonPath('requestedCharacter', 'Stormtroopers');
        $response->assertJsonPath('secureChanging', 1);
        $response->assertJsonPath('blasters', 1);
        $response->assertJsonPath('lightsabers', 0);
        $response->assertJsonPath('parking', 1);
        $response->assertJsonPath('mobility', 1);
        $response->assertJsonPath('amenities', 'Restrooms and water');
        $response->assertJsonPath('referred', 'Command Staff');
        $response->assertJsonPath('limitedEvent', 1);
        $response->assertJsonPath('allowTentative', 1);
        $response->assertJsonPath('isLimited', true);
        $response->assertJsonPath('limitTotalTroopers', 10);
        $response->assertJsonPath('limitHandlers', 2);
    }

    public function test_get_costumes_for_trooper_accepts_forum_user_id_and_returns_approved_costumes(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create([
            Costume::NAME => 'Stormtrooper',
        ]);

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '15802',
        ]);

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-15502')
            ->create();

        $organizationCostume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->withPrefix('TK')
            ->create();

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organizationCostume)
            ->create();

        $response = $this->get(route('api.mobile', [
                        'action' => 'get_costumes_for_trooper',
                        'trooperid' => 15802,
                        'friendid' => 0,
                    ]));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $costume->id);
        $response->assertJsonPath('0.name', 'Stormtrooper');
        $response->assertJsonPath('0.abbreviation', 'TK ');
        $response->assertJsonPath('0.club', $organization->name);
    }

    public function test_sign_up_accepts_string_status_and_creates_signup(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '15802',
        ]);
        
        $response = $this->get(route('api.mobile', [
                        'action' => 'sign_up',
                        'trooperid' => 15802,
                        'addedby' => 0,
                        'troopid' => $event->id,
                        'status' => EventTrooperStatus::GOING->value,
                        'costume' => $costume->id,
                        'backupcostume' => 0,
                    ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('success_message', 'Success!');

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $costume->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_sign_up_rejects_legacy_numeric_status_code(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $costume = Costume::factory()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '15802',
        ]);
        
        $response = $this->get(route('api.mobile', [
                        'action' => 'sign_up',
                        'trooperid' => 15802,
                        'addedby' => 0,
                        'troopid' => $event->id,
                        'status' => 0,
                        'costume' => $costume->id,
                        'backupcostume' => 0,
                    ]));

        $response->assertStatus(400);
        $response->assertJsonPath(
            'error',
            '"0" is not a valid backing value for enum App\\Enums\\EventTrooperStatus',
        );
    }

    public function test_cancel_shift_is_forbidden_for_manual_selection_event(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::MANUAL_SELECTION,
        ]);
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '15802',
        ]);
        
        $response = $this->get(route('api.mobile', [
                        'action' => 'cancel_shift',
                        'trooperid' => 15802,
                        'shiftid' => $shift->id,
                    ]));

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_cancel_troop_is_forbidden_for_manual_selection_event(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::MANUAL_SELECTION,
        ]);
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '15802',
        ]);
        
        $response = $this->get(route('api.mobile', [
                        'action' => 'cancel_troop',
                        'trooperid' => 15802,
                        'troopid' => $event->id,
                    ]));

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_cancel_guest_is_forbidden_for_manual_selection_event(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::MANUAL_SELECTION,
        ]);
        $shift = EventShift::factory()->forEvent($event)->create();

        $guest = EventGuest::factory()->forEventShift($shift)->create([
            EventGuest::ADDED_BY_TROOPER_ID => $trooper->id,
            EventGuest::STATUS => EventGuestStatus::GOING->value,
        ]);

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '15802',
        ]);
        
        $response = $this->get(route('api.mobile', [
                        'action' => 'cancel_guest',
                        'trooperid' => 15802,
                        'guestid' => $guest->id,
                    ]));

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);

        $this->assertDatabaseHas('tt_event_guests', [
            EventGuest::ID => $guest->id,
            EventGuest::STATUS => EventGuestStatus::GOING->value,
        ]);
    }
}