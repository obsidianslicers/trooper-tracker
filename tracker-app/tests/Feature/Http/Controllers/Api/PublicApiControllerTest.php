<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\EventUpload;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_trooper_history_leaves_out_events_whose_type_does_not_count(): void
    {
        $trooper = Trooper::factory()->create();
        $uncounted = EventType::factory()->uncounted()->create();

        foreach ([EventType::idNamed('Regular'), $uncounted->id] as $type)
        {
            $event = Event::factory()->asClosed()->create([Event::EVENT_TYPE_ID => $type]);
            $shift = EventShift::factory()->forEvent($event)->create();
            EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();
        }

        $response = $this->get(route('api.public', ['trooperid' => $trooper->id]));

        $response->assertOk();
        $response->assertJsonPath('0.troopCount', 1);
    }

    public function test_roster_returns_empty_roster_when_org_not_found(): void
    {
        $response = $this->get(route('api.public', ['roster' => '', 'org' => 9999]));

        $response->assertOk();
        $response->assertSee('No members to display.');
    }

    public function test_roster_returns_html_with_active_member_names(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withDisplayName('Test Member')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->create();

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertSee('Test Member');
    }

    public function test_roster_does_not_show_non_members(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withDisplayName('Non Member')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->create();

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertDontSee('Non Member');
    }

    public function test_roster_caches_response_after_first_request(): void
    {
        $org = Organization::factory()->create();

        $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $this->assertTrue(Cache::has("tracker:roster:{$org->id}"));
    }

    public function test_roster_returns_cached_html_on_subsequent_requests(): void
    {
        $org = Organization::factory()->create();

        Cache::put("tracker:roster:{$org->id}", '<p>cached content</p>', 86400);

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertSee('cached content');
    }

    public function test_roster_does_not_cache_missing_org_response(): void
    {
        $this->get(route('api.public', ['roster' => '', 'org' => 9999]));

        $this->assertFalse(Cache::has('tracker:roster:9999'));
    }

    public function test_roster_places_moderators_in_staff_section(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withDisplayName('Staff Member')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->asModerator()
            ->create();

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Leadership, Liaisons, and Unit Staff', 'Staff Member']);
    }

    public function test_roster_places_administrators_in_staff_section(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->asSystemOwner()->withDisplayName('Admin Member')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->create();

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Leadership, Liaisons, and Unit Staff', 'Admin Member']);
    }

    public function test_photos_skip_uploads_whose_files_are_missing(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/events/1/originals/kept.jpg', 'x');
        $kept = $this->photo('uploads/events/1/originals/kept.jpg');
        $this->photo('uploads/events/1/originals/gone.jpg');

        $response = $this->get(route('api.public', ['photos' => 1, 'amount' => 5]));

        $response->assertOk();
        $response->assertJsonCount(1, '0');
        $response->assertJsonPath('0.0.troopID', $kept->event_id);
        $response->assertJsonPath('0.0.imageUrl', Storage::url('uploads/events/1/originals/kept.jpg'));
    }

    public function test_photo_slideshow_uses_the_stored_image_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/events/1/originals/kept.jpg', 'x');
        $this->photo('uploads/events/1/originals/kept.jpg');

        $response = $this->get(route('api.public', ['photos' => 1, 'amount' => 5, 'slideshow' => 1]));

        $response->assertOk();
        $response->assertSee(Storage::url('uploads/events/1/originals/kept.jpg'), false);
        $response->assertDontSee('images/uploads/resize', false);
    }

    public function test_photo_slideshow_uses_the_legacy_resized_copy_when_present(): void
    {
        Storage::fake('legacy_uploads');
        Storage::disk('legacy_uploads')->put('2019-old.jpeg', 'x');
        Storage::disk('legacy_uploads')->put('resize/2019-old.jpg', 'x');
        $this->photo('2019-old.jpeg');

        $response = $this->get(route('api.public', ['photos' => 1, 'amount' => 5, 'slideshow' => 1]));

        $response->assertSee(url('images/uploads/resize/2019-old.jpg'), false);
    }

    private function photo(string $path): EventUpload
    {
        return EventUpload::factory()->create([
            EventUpload::IMAGE_PATH_LG => $path,
            EventUpload::IMAGE_PATH_SM => $path,
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);
    }
}
