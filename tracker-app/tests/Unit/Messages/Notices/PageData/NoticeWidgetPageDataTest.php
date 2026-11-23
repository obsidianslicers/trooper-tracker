<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Notices\PageData;

use App\Messages\Notices\PageData\NoticeWidgetPageData;
use App\Models\Notice;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeWidgetPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_the_notice_when_only_one_is_unread(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->asGlobal()->create();

        $subject = new NoticeWidgetPageData($trooper);
        $result = $subject->handle();

        $this->assertSame(1, $result['count']);
        $this->assertTrue($notice->is($result['notice']));
    }

    public function test_handle_only_counts_several_unread_notices(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        Notice::factory()->asGlobal()->count(2)->create();

        $result = (new NoticeWidgetPageData($trooper))->handle();

        $this->assertSame(2, $result['count']);
        $this->assertNull($result['notice']);
    }
}
