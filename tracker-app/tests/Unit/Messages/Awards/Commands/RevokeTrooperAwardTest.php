<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Awards\Commands;

use App\Events\AwardRevoked;
use App\Messages\Awards\Commands\RevokeTrooperAward;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RevokeTrooperAwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_takes_the_award_back_and_names_the_trooper(): void
    {
        Event::fake([AwardRevoked::class]);
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $award_trooper = AwardTrooper::factory()->forAward($award)->forTrooper($trooper)->create();

        $result = (new RevokeTrooperAward($award, $award_trooper->id))->handle();

        $this->assertSame($trooper->display_name, $result);
        $this->assertSoftDeleted($award_trooper);
        Event::assertDispatched(AwardRevoked::class);
    }

    public function test_handle_ignores_a_grant_of_another_award(): void
    {
        Event::fake([AwardRevoked::class]);
        $award = Award::factory()->create();
        $other = AwardTrooper::factory()->forAward(Award::factory()->create())
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->create();

        $result = (new RevokeTrooperAward($award, $other->id))->handle();

        $this->assertNull($result);
        $this->assertNotSoftDeleted($other);
        Event::assertNotDispatched(AwardRevoked::class);
    }
}
