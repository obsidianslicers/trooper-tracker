<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use App\Policies\EventUploadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventUploadPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_type_allows_uploader(): void
    {
        $subject = new EventUploadPolicy;
        $trooper = Trooper::factory()->asMember()->create();
        $upload = EventUpload::factory()->for(Event::factory())->create([
            EventUpload::TROOPER_ID => $trooper->id,
        ]);

        $this->assertTrue($subject->changeType($trooper, $upload));
    }

    public function test_change_type_allows_officer_who_can_update_the_event(): void
    {
        $subject = new EventUploadPolicy;
        $officer = Trooper::factory()->asSystemOwner()->create();
        $upload = EventUpload::factory()->for(Event::factory())->create();

        $this->assertTrue($subject->changeType($officer, $upload));
    }

    public function test_change_type_denies_other_members(): void
    {
        $subject = new EventUploadPolicy;
        $trooper = Trooper::factory()->asMember()->create();
        $upload = EventUpload::factory()->for(Event::factory())->create();

        $this->assertFalse($subject->changeType($trooper, $upload));
    }
}
