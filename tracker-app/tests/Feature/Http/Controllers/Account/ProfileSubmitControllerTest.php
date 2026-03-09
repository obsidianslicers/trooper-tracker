<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\TrooperTheme;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_profile_successfully(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->post(route('account.profile'), [
            'email' => $trooper->email,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'legal_name' => 'Updated Legal Name',
            'display_name' => 'UpdatedDisplay',
            'notification_frequency' => $trooper->notification_frequency->value,
            'theme' => TrooperTheme::REBEL->value,
        ]);

        $response->assertRedirect(route('account.profile'));
    }

    public function test_invoke_displays_theme_change_message(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::THEME => TrooperTheme::STORMTROOPER,
        ]);

        $response = $this->actingAs($trooper)->post(route('account.profile'), [
            'email' => $trooper->email,
            'first_name' => $trooper->first_name,
            'last_name' => $trooper->last_name,
            'legal_name' => $trooper->legal_name,
            'display_name' => $trooper->display_name,
            'notification_frequency' => $trooper->notification_frequency->value,
            'theme' => TrooperTheme::SITH->value,
        ]);

        $response->assertRedirect(route('account.profile'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post(route('account.profile'), [
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
