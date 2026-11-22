<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Notices\Commands;

use App\Enums\NoticeType;
use App\Messages\Notices\Commands\UpdateNotice;
use App\Models\Notice;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UpdateNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_changes_the_notice_but_not_its_organization(): void
    {
        $organization = Organization::factory()->create();
        $notice = Notice::factory()->forOrganization($organization)->create();

        $subject = new UpdateNotice(
            notice: $notice,
            title: 'Rescheduled',
            type: NoticeType::DANGER,
            starts_at: Carbon::parse('2030-07-01 09:00'),
            ends_at: Carbon::parse('2030-07-02 09:00'),
            message: 'New date.',
        );
        $subject->handle();

        $notice->refresh();
        $this->assertSame('Rescheduled', $notice->title);
        $this->assertSame(NoticeType::DANGER, $notice->type);
        $this->assertSame('New date.', $notice->message);
        $this->assertSame($organization->id, $notice->organization_id);
    }
}
