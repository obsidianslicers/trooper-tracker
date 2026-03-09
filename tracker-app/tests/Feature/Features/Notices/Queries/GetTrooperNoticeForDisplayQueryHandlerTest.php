<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Notices\Queries;

use App\Features\Notices\Queries\GetTrooperNoticeForDisplayQuery;
use App\Features\Notices\Queries\GetTrooperNoticeForDisplayQueryHandler;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperNoticeForDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_count_and_null_notice_when_no_notices_exist(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $subject = new GetTrooperNoticeForDisplayQueryHandler();

        $result = $subject(new GetTrooperNoticeForDisplayQuery($trooper));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('notice', $result);
        $this->assertSame(0, $result['count']);
        $this->assertNull($result['notice']);
    }

    public function test_invoke_returns_count_and_notice_when_exactly_one_notice_exists(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $notice = Notice::factory()->asGlobal()->asActive()->withTitle('Single Notice')->create();

        $subject = new GetTrooperNoticeForDisplayQueryHandler();

        $result = $subject(new GetTrooperNoticeForDisplayQuery($trooper));

        $this->assertSame(1, $result['count']);
        $this->assertNotNull($result['notice']);
        $this->assertSame($notice->id, $result['notice']->id);
        $this->assertSame('Single Notice', $result['notice']->title);
    }

    public function test_invoke_returns_count_and_null_notice_when_multiple_notices_exist(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        Notice::factory()->asGlobal()->asActive()->withTitle('Notice One')->create();
        Notice::factory()->asGlobal()->asActive()->withTitle('Notice Two')->create();

        $subject = new GetTrooperNoticeForDisplayQueryHandler();

        $result = $subject(new GetTrooperNoticeForDisplayQuery($trooper));

        $this->assertSame(2, $result['count']);
        $this->assertNull($result['notice']);
    }

    public function test_invoke_respects_unread_only_filter(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $unread_notice = Notice::factory()->asGlobal()->asActive()->withTitle('Unread')->create();
        $read_notice = Notice::factory()->asGlobal()->asActive()->withTitle('Read')->create();

        NoticeTrooper::factory()
            ->forNotice($read_notice)
            ->forTrooper($trooper)
            ->asRead()
            ->create();

        $subject = new GetTrooperNoticeForDisplayQueryHandler();

        $result = $subject(new GetTrooperNoticeForDisplayQuery($trooper, true));

        $this->assertSame(1, $result['count']);
        $this->assertNotNull($result['notice']);
        $this->assertSame('Unread', $result['notice']->title);
    }
}
