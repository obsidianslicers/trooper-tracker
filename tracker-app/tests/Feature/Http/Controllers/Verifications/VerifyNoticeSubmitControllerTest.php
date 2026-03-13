<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Verifications;

use App\Models\Trooper;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerifyNoticeSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Guest Access
    // -------------------------------------------------------------------------

    public function test_invoke_redirects_unauthenticated_trooper_to_login(): void
    {
        Notification::fake();

        $response = $this->post(route('verification.notice'));

        $response->assertRedirect(route('auth.login'));
        Notification::assertNothingSent();
    }

    // -------------------------------------------------------------------------
    // Notification Dispatch
    // -------------------------------------------------------------------------

    public function test_invoke_sends_verification_notification_to_trooper(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asActive()->withUnverifiedEmail()->create();

        $this->actingAs($trooper)->post(route('verification.notice'));

        Notification::assertSentTo($trooper, VerifyEmail::class);
    }

    public function test_invoke_sends_exactly_one_notification(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asActive()->withUnverifiedEmail()->create();

        $this->actingAs($trooper)->post(route('verification.notice'));

        Notification::assertSentTo($trooper, VerifyEmail::class, static function (): bool
        {
            return true;
        });

        Notification::assertCount(1);
    }

    public function test_invoke_sends_notification_even_when_email_already_verified(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->actingAs($trooper)->post(route('verification.notice'));

        Notification::assertSentTo($trooper, VerifyEmail::class);
    }

    // -------------------------------------------------------------------------
    // Flash Message
    // -------------------------------------------------------------------------

    public function test_invoke_flashes_success_message_to_session(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asActive()->withUnverifiedEmail()->create();

        $response = $this->actingAs($trooper)->post(route('verification.notice'));

        $response->assertSessionHas('flash_messages');
    }

    // -------------------------------------------------------------------------
    // Redirect
    // -------------------------------------------------------------------------

    public function test_invoke_redirects_to_verification_notice_after_resend(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asActive()->withUnverifiedEmail()->create();

        $response = $this->actingAs($trooper)->post(route('verification.notice'));

        $response->assertRedirect(route('verification.notice'));
    }
}
