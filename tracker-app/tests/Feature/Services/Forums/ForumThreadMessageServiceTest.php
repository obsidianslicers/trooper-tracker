<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Forums;

use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Services\Forums\ForumThreadMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumThreadMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_thread_message_includes_title_cased_roster_statuses(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->create();

        $shift = EventShift::factory()
            ->forEvent($event)
            ->create();

        $trooper = Trooper::factory()
            ->withDisplayName('TK-421')
            ->create();

        $costume = Costume::factory()
            ->withName('Shadow Trooper')
            ->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->state([
                EventTrooper::IS_HANDLER => false,
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::SIGNED_UP_AT => now(),
            ])
            ->create();

        $subject = new ForumThreadMessageService;

        $result = $subject->buildThreadMessage($event);

        $this->assertStringContainsString('[b]Roster:[/b]', $result);
        $this->assertStringContainsString('-[i]Stand By[/i]: TK-421 (Shadow Trooper)', $result);
        $this->assertStringContainsString('[url]'.url('/events/'.$event->id).'[/url]', $result);
    }
}