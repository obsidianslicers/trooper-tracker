<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\UpdateNoticeRead;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateNoticeReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_notice_trooper_record_when_none_exists(): void
    {
        $trooper = Trooper::factory()->create();
        $notice = Notice::factory()->create();

        $subject = new UpdateNoticeRead($trooper, $notice);

        $subject->handle();

        $this->assertDatabaseHas('tt_notice_troopers', [
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function test_handle_marks_existing_notice_as_read_for_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $notice = Notice::factory()->create();

        $existing = NoticeTrooper::factory()
            ->forNotice($notice)
            ->forTrooper($trooper)
            ->asUnread()
            ->create();

        NoticeTrooper::factory()
            ->forNotice($notice)
            ->forTrooper($other_trooper)
            ->asUnread()
            ->create();

        $subject = new UpdateNoticeRead($trooper, $notice);

        $subject->handle();

        $existing->refresh();

        $this->assertTrue($existing->is_read);
        $this->assertDatabaseHas('tt_notice_troopers', [
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::IS_READ => true,
        ]);
        $this->assertDatabaseHas('tt_notice_troopers', [
            NoticeTrooper::TROOPER_ID => $other_trooper->id,
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::IS_READ => false,
        ]);
    }
}
