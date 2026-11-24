<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\Resources;

use App\Messages\ServiceRecords\Resources\CommandStaffMemberResource;
use App\Messages\ServiceRecords\Resources\GalleryPhotoResource;
use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRecordListResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_photo_names_the_event_uploader_and_tagged_troopers(): void
    {
        $event = Event::factory()->create([Event::NAME => 'Hospital visit']);
        $uploader = Trooper::factory()->withDisplayName('Uploader')->create();
        $upload = EventUpload::factory()->create([
            EventUpload::EVENT_ID => $event->id,
            EventUpload::TROOPER_ID => $uploader->id,
        ]);
        $upload->troopers()->attach([
            Trooper::factory()->withDisplayName('Alpha')->create()->id,
            Trooper::factory()->withDisplayName('Bravo')->create()->id,
        ]);

        $subject = (new GalleryPhotoResource($upload->load('event', 'trooper', 'troopers')))->resolve();

        $this->assertSame('Hospital visit', $subject['event_name']);
        $this->assertSame(route('events.display', ['event' => $event]), $subject['event_url']);
        $this->assertSame('Uploader', $subject['uploaded_by']);
        $this->assertSame('Alpha, Bravo', $subject['tagged']);
        $this->assertSame($upload->small_url, $subject['small_url']);
    }

    public function test_command_staff_member_lists_the_clubs_they_moderate(): void
    {
        $trooper = Trooper::factory()->asModerator()->withDisplayName('Mod')->create();
        $trooper->setAttribute('cs_organizations', collect([
            new Organization([Organization::NAME => '501st Legion']),
            new Organization([Organization::NAME => 'Rebel Legion']),
        ]));

        $subject = (new CommandStaffMemberResource($trooper))->resolve();

        $this->assertSame('Mod', $subject['display_name']);
        $this->assertSame('501st Legion, Rebel Legion', $subject['organizations']);
        $this->assertSame(route('service-records.trooper', ['trooper' => $trooper]), $subject['url']);
    }
}
