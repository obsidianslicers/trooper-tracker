<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Notices\Commands;

use App\Enums\NoticeType;
use App\Messages\Notices\Commands\CreateNotice;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_saves_the_notice(): void
    {
        $organization = Organization::factory()->create();
        $starts_at = Carbon::parse('2030-06-01 13:00');

        $subject = new CreateNotice(
            organization_id: $organization->id,
            title: 'Muster',
            type: NoticeType::WARNING,
            starts_at: $starts_at,
            ends_at: $starts_at->copy()->addDay(),
            message: 'North gate.',
        );
        $notice = $subject->handle();

        $this->assertTrue($notice->exists);
        $this->assertSame($organization->id, $notice->organization_id);
        $this->assertSame(NoticeType::WARNING, $notice->fresh()->type);
        $this->assertSame('2030-06-01 13:00', $notice->fresh()->starts_at->format('Y-m-d H:i'));
    }
}
