<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Verifications;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyNoticeControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Guest Access
    // -------------------------------------------------------------------------

    public function test_invoke_redirects_unauthenticated_trooper_to_login(): void
    {
        $response = $this->get(route('verification.notice'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_notice_view_for_active_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertViewIs('pages.verifications.notice');
    }

    public function test_invoke_renders_notice_view_for_pending_trooper_with_unverified_email(): void
    {
        $trooper = Trooper::factory()->asPending()->withUnverifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertViewIs('pages.verifications.notice');
    }

    public function test_invoke_renders_notice_view_for_pending_trooper_with_verified_email(): void
    {
        $trooper = Trooper::factory()->asPending()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertViewIs('pages.verifications.notice');
    }

    public function test_invoke_renders_notice_view_for_retired_trooper(): void
    {
        $trooper = Trooper::factory()->asRetired()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertViewIs('pages.verifications.notice');
    }

    // -------------------------------------------------------------------------
    // Response Shape
    // -------------------------------------------------------------------------

    public function test_invoke_returns_html_content_type(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_invoke_does_not_redirect_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('verification.notice'));

        $response->assertOk();
    }
}
