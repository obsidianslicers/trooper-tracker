<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Awards\Commands;

use App\Events\AwardGranted;
use App\Messages\Awards\Commands\GrantTrooperAward;
use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GrantTrooperAwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_grants_the_award_and_announces_it(): void
    {
        Event::fake([AwardGranted::class]);
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $subject = new GrantTrooperAward($award, $trooper, Carbon::parse('2026-01-01'));
        $award_trooper = $subject->handle();

        $this->assertTrue($award->troopers()->whereKey($trooper->id)->exists());
        $this->assertSame('2026-01-01', $award_trooper->award_date->format('Y-m-d'));
        Event::assertDispatched(AwardGranted::class);
    }
}
