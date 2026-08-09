<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\TrooperTheme;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_profile_and_renders_account_index(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::LEGAL_NAME => 'Old Legal Name',
            Trooper::DISPLAY_NAME => 'Old Display Name',
            Trooper::THEME => TrooperTheme::STORMTROOPER,
            Trooper::PHONE => '555-0000',
            Trooper::DISPLAY_COSTUME_ID => null,
        ]);

        $payload = [
            Trooper::LEGAL_NAME => 'Updated Legal Name',
            Trooper::DISPLAY_NAME => 'Updated Display Name',
            Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            Trooper::PHONE => '(555) 123-4567',
            Trooper::DISPLAY_COSTUME_ID => null,
        ];

        $response = $this->actingAs($trooper)->post(route('account.update-profile'), $payload);

        $trooper->refresh();

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('account/Index')
            ->has('flash.success')
        );

        $this->assertSame('Updated Legal Name', $trooper->legal_name);
        $this->assertSame('Updated Display Name', $trooper->display_name);
        $this->assertSame(TrooperTheme::STORMTROOPER, $trooper->theme);
        $this->assertSame('5551234567', $trooper->phone);
        $this->assertNull($trooper->display_costume_id);
    }

    public function test_invoke_includes_warning_flash_when_theme_changes(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::THEME => TrooperTheme::STORMTROOPER,
        ]);

        $response = $this->actingAs($trooper)->post(route('account.update-profile'), [
            Trooper::LEGAL_NAME => $trooper->legal_name,
            Trooper::DISPLAY_NAME => $trooper->display_name,
            Trooper::THEME => TrooperTheme::SITH->value,
            Trooper::PHONE => $trooper->phone,
            Trooper::DISPLAY_COSTUME_ID => null,
        ]);

        $trooper->refresh();

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('account/Index')
            ->has('flash.success')
            ->has('flash.warning')
        );

        $this->assertSame(TrooperTheme::SITH, $trooper->theme);
    }

    public function test_invoke_redirects_guest_to_login(): void
    {
        $response = $this->post(route('account.update-profile'), [
            Trooper::LEGAL_NAME => 'Guest Name',
            Trooper::DISPLAY_NAME => 'Guest Display',
            Trooper::THEME => TrooperTheme::REBEL->value,
            Trooper::PHONE => null,
            Trooper::DISPLAY_COSTUME_ID => null,
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}