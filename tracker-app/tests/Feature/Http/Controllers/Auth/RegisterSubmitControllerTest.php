<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Mail\Auth\TrooperRegistered;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegisterSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_redirects_on_successful_registration(): void
    {
        Mail::fake();
        Queue::fake();

        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        $organization = Organization::factory()->withNodePath('org.root')->create();

        $registration_data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'legal_name' => 'John Doe',
            'display_name' => 'JohnDoe',
            'email' => 'johndoe@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'account_type' => 'member',
            'registration_method' => 'email',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'tkid' => 'TK-12345',
                    'should_notify' => '1',
                ],
            ],
        ];

        $response = $this->post(route('auth.register'), $registration_data);

        $response->assertRedirect(route('auth.thank-you'));
    }
}
