<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Mail\Account\AccountDeletionRequestedMail;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DeletionRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_trooper_can_request_deletion(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->delete(route('account.delete'));

        $response->assertRedirect(route('auth.login'));
        $this->assertNotNull($trooper->fresh()->deletion_requested_at);
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->delete(route('account.delete'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_trooper_with_pending_deletion_cannot_request_again(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::DELETION_REQUESTED_AT => now()->subDays(1),
        ]);

        $response = $this->actingAs($trooper)->delete(route('account.delete'));

        $response->assertForbidden();
    }

    public function test_cancel_clears_deletion_request(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::DELETION_REQUESTED_AT => now()->subDays(5),
        ]);

        $response = $this->actingAs($trooper)->delete(route('account.delete.cancel'));

        $response->assertRedirect(route('account.index'));
        $this->assertNull($trooper->fresh()->deletion_requested_at);
    }
}
