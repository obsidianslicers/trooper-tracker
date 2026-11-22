<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Notices\Queries;

use App\Messages\Notices\Queries\GetNoticesForModerator;
use App\Models\Filters\NoticeFilter;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetNoticesForModeratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_active_notices_the_moderator_moderates(): void
    {
        $garrison = Organization::factory()->asRegion()->create();
        $other = Organization::factory()->asRegion()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($garrison)->asModerator()->create();
        $mine = Notice::factory()->forOrganization($garrison)->asActive()->create();
        Notice::factory()->forOrganization($other)->asActive()->create();
        Notice::factory()->forOrganization($garrison)->create([
            Notice::STARTS_AT => now()->subWeeks(2),
            Notice::ENDS_AT => now()->subWeek(),
        ]);

        $subject = new GetNoticesForModerator(new NoticeFilter(new Request), $moderator);
        $result = $subject->handle();

        $this->assertSame([$mine->id], collect($result->items())->pluck('id')->all());
    }

    public function test_handle_pages_the_results(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        Notice::factory()->count(3)->asActive()->create();

        $subject = new GetNoticesForModerator(new NoticeFilter(new Request), $admin, page_size: 2);
        $result = $subject->handle();

        $this->assertCount(2, $result->items());
        $this->assertSame(3, $result->total());
    }
}
