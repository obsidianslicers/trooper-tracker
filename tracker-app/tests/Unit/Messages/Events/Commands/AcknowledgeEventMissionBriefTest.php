<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\AcknowledgeEventMissionBrief;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcknowledgeEventMissionBriefTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_records_the_acknowledgement(): void
    {
        $event = Event::factory()->create([Event::REQUIRE_MISSION_BRIEF_ACK => true]);
        $trooper = Trooper::factory()->asActive()->create();

        $subject = new AcknowledgeEventMissionBrief($event, $trooper);
        $subject->handle();

        $this->assertTrue($event->hasMissionBriefAcknowledgementFor($trooper));
    }

    public function test_handle_acknowledging_again_keeps_one_record(): void
    {
        $event = Event::factory()->create([Event::REQUIRE_MISSION_BRIEF_ACK => true]);
        $trooper = Trooper::factory()->asActive()->create();

        $subject = new AcknowledgeEventMissionBrief($event, $trooper);
        $subject->handle();
        $subject->handle();

        $this->assertDatabaseCount('tt_event_mission_acks', 1);
    }
}
