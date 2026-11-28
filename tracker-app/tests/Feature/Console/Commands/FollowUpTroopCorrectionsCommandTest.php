<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Messages\Events\Commands\Corrections\FollowUpTroopCorrections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class FollowUpTroopCorrectionsCommandTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_reports_what_it_followed_up(): void
    {
        $this->fakeMessages([FollowUpTroopCorrections::class => ['reminded' => 2, 'escalated' => 1]]);

        $this->artisan('tracker:follow-up-troop-corrections')
            ->expectsOutput('Reminded 2, handed 1 to moderators.')
            ->assertSuccessful();
    }
}
