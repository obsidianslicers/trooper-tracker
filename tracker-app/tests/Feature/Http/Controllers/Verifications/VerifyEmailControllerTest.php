<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Verifications;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_verifies_authenticated_trooper_with_valid_signed_url(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withUnverifiedEmail()
            ->create();

        $response = $this->actingAs($trooper)
            ->get($this->verification_url_for_trooper($trooper));

        $response->assertRedirect(route('account.profile'));
        $this->assertTrue($trooper->fresh()->hasVerifiedEmail());
    }

    public function test_invoke_redirects_guest_to_login_for_signed_request(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withUnverifiedEmail()
            ->create();

        $response = $this->get($this->verification_url_for_trooper($trooper));

        $response->assertRedirect(route('auth.login'));
        $this->assertFalse($trooper->fresh()->hasVerifiedEmail());
    }

    public function test_invoke_rejects_unsigned_request(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withUnverifiedEmail()
            ->create();

        $response = $this->actingAs($trooper)->get(route('verification.verify', [
            'id' => $trooper->getKey(),
            'hash' => sha1($trooper->getEmailForVerification()),
        ]));

        $response->assertRedirect(route('verification.notice'));
        $this->assertFalse($trooper->fresh()->hasVerifiedEmail());
    }

    public function test_invoke_redirects_verified_trooper_to_setup_without_changing_state(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withVerifiedEmail()
            ->create();

        $verified_at = $trooper->email_verified_at;

        $response = $this->actingAs($trooper)
            ->get($this->verification_url_for_trooper($trooper));

        $response->assertRedirect(route('account.profile'));
        $this->assertTrue($trooper->fresh()->hasVerifiedEmail());
        $this->assertTrue($trooper->fresh()->email_verified_at?->eq($verified_at));
    }

    private function verification_url_for_trooper(Trooper $trooper): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $trooper->getKey(),
                'hash' => sha1($trooper->getEmailForVerification()),
            ],
        );
    }
}