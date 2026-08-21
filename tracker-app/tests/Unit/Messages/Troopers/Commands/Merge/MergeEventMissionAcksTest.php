<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeEventMissionAcks;
use App\Models\Event;
use App\Models\EventMissionAck;
use App\Models\Trooper;
use Database\Factories\Base\EventMissionAckFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeEventMissionAcksTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_event_mission_acks_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $event_one = Event::factory()->create();
        $event_two = Event::factory()->create();

        $ack_one = EventMissionAckFactory::new()->create([
            EventMissionAck::EVENT_ID => $event_one->id,
            EventMissionAck::TROOPER_ID => $source_trooper->id,
        ]);

        $ack_two = EventMissionAckFactory::new()->create([
            EventMissionAck::EVENT_ID => $event_two->id,
            EventMissionAck::TROOPER_ID => $source_trooper->id,
        ]);

        MergeEventMissionAcks::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_mission_acks', [
            EventMissionAck::ID => $ack_one->id,
            EventMissionAck::EVENT_ID => $event_one->id,
            EventMissionAck::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseHas('tt_event_mission_acks', [
            EventMissionAck::ID => $ack_two->id,
            EventMissionAck::EVENT_ID => $event_two->id,
            EventMissionAck::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_mission_acks', [
            EventMissionAck::ID => $ack_one->id,
            EventMissionAck::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_mission_acks', [
            EventMissionAck::ID => $ack_two->id,
            EventMissionAck::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_merges_duplicate_event_ack_and_keeps_latest_acknowledged_at(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $event = Event::factory()->create();

        $target_ack = EventMissionAckFactory::new()->create([
            EventMissionAck::EVENT_ID => $event->id,
            EventMissionAck::TROOPER_ID => $target_trooper->id,
            EventMissionAck::ACKNOWLEDGED_AT => now()->subDay(),
        ]);

        $source_ack = EventMissionAckFactory::new()->create([
            EventMissionAck::EVENT_ID => $event->id,
            EventMissionAck::TROOPER_ID => $source_trooper->id,
            EventMissionAck::ACKNOWLEDGED_AT => now(),
        ]);

        MergeEventMissionAcks::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $target_ack->refresh();

        $this->assertDatabaseHas('tt_event_mission_acks', [
            EventMissionAck::ID => $target_ack->id,
            EventMissionAck::EVENT_ID => $event->id,
            EventMissionAck::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_mission_acks', [
            EventMissionAck::ID => $source_ack->id,
        ]);

        $this->assertSame(1, EventMissionAck::query()->count());
        $this->assertTrue($target_ack->acknowledged_at->equalTo($source_ack->acknowledged_at));
    }
}
