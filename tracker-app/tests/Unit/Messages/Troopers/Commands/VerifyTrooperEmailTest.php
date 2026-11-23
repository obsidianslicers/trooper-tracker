<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\VerifyTrooperEmail;
use App\Models\Trooper;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VerifyTrooperEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_marks_the_email_verified(): void
    {
        Event::fake([Verified::class]);
        $trooper = Trooper::factory()->asActive()->withUnverifiedEmail()->create();

        $subject = new VerifyTrooperEmail($trooper);
        $subject->handle();

        $this->assertTrue($trooper->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class, fn (Verified $event) => $event->user->is($trooper));
    }

    public function test_handle_leaves_an_already_verified_email_alone(): void
    {
        Event::fake([Verified::class]);
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $verified_at = $trooper->email_verified_at;

        $subject = new VerifyTrooperEmail($trooper);
        $subject->handle();

        $this->assertEquals($verified_at, $trooper->fresh()->email_verified_at);
        Event::assertNotDispatched(Verified::class);
    }
}
