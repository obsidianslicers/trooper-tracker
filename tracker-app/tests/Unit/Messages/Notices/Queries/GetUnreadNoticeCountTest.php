<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Notices\Queries;

use App\Messages\Notices\Queries\GetUnreadNoticeCount;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetUnreadNoticeCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_unread_visible_notices_for_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();

        $unread_notice = Notice::factory()
            ->asGlobal()
            ->withTitle('Unread Notice')
            ->create([
                Notice::STARTS_AT => now()->subHour(),
            ]);

        $second_unread_notice = Notice::factory()
            ->asGlobal()
            ->withTitle('Second Unread Notice')
            ->create([
                Notice::STARTS_AT => now()->subHours(2),
            ]);

        $read_notice = Notice::factory()
            ->asGlobal()
            ->withTitle('Read Notice')
            ->create();

        NoticeTrooper::factory()
            ->forNotice($read_notice)
            ->forTrooper($trooper)
            ->asRead()
            ->create();

        NoticeTrooper::factory()
            ->forNotice($unread_notice)
            ->forTrooper($other_trooper)
            ->asUnread()
            ->create();

        NoticeTrooper::factory()
            ->forNotice($second_unread_notice)
            ->forTrooper($trooper)
            ->asUnread()
            ->create();

        $subject = new GetUnreadNoticeCount($trooper);

        $result = $subject->handle();

        $this->assertSame(2, $result);
        $this->assertDatabaseHas('tt_notice_troopers', [
            'notice_id' => $unread_notice->id,
            'trooper_id' => $other_trooper->id,
            'is_read' => false,
        ]);
        $this->assertDatabaseHas('tt_notice_troopers', [
            'notice_id' => $second_unread_notice->id,
            'trooper_id' => $trooper->id,
            'is_read' => false,
        ]);
    }
}
