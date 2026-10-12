<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Notices\Queries;

use App\Messages\Notices\Queries\GetUnreadNotices;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetUnreadNoticesTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_unread_notices_for_trooper_sorted_by_start_time(): void
    {
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();

        $later_notice = Notice::factory()
            ->asGlobal()
            ->withTitle('Later Notice')
            ->create([Notice::STARTS_AT => now()->subHour()]);
        $earlier_notice = Notice::factory()
            ->asGlobal()
            ->withTitle('Earlier Notice')
            ->create([Notice::STARTS_AT => now()->subHours(2)]);
        $read_notice = Notice::factory()->asGlobal()->withTitle('Read Notice')->create();

        NoticeTrooper::factory()
            ->forNotice($read_notice)
            ->forTrooper($trooper)
            ->asRead()
            ->create();
        NoticeTrooper::factory()
            ->forNotice($later_notice)
            ->forTrooper($other_trooper)
            ->asRead()
            ->create();

        $subject = new GetUnreadNotices($trooper);

        $result = $subject->handle();

        $this->assertSame(
            [$earlier_notice->getKey(), $later_notice->getKey()],
            $result->modelKeys(),
        );
    }
}
