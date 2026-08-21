<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_completes_setup_and_redirects_to_costumes(): void
    {
        $trooper = Trooper::factory()->asActive()->withSetupIncomplete()->create();
        $organization = Organization::factory()->withNodePath('org.root')->create();

        $response = $this->actingAs($trooper)->post(route('account.setup-submit'), [
            'email' => $trooper->email,
            'legal_name' => $trooper->legal_name,
            'theme' => $trooper->theme->value,
            'notification_frequency' => $trooper->notification_frequency->value,
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('account.index'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post(route('account.setup-submit'), [
            'organizations' => [],
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
