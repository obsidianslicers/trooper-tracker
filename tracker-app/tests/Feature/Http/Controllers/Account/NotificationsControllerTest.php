<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_notification_settings_page(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.notifications'));

        $response->assertOk();
        $response->assertViewIs('pages.account.notifications');
        $response->assertViewHas('organizations');
        $response->assertViewHas('notification_frequency');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.notifications'));

        $response->assertRedirect(route('auth.login'));
    }
}
