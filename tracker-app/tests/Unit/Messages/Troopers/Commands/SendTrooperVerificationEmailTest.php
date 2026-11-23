<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\SendTrooperVerificationEmail;
use App\Models\Trooper;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTrooperVerificationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_the_trooper_a_verification_link(): void
    {
        Notification::fake();
        $trooper = Trooper::factory()->asActive()->withUnverifiedEmail()->create();

        $subject = new SendTrooperVerificationEmail($trooper);
        $subject->handle();

        Notification::assertSentToTimes($trooper, VerifyEmail::class, 1);
    }
}
