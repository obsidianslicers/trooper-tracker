<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Widgets;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportDisplayHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_support_widget_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('widgets.support-htmx'));

        $response->assertOk();
        $response->assertViewIs('widgets.support');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('widgets.support-htmx'));

        $response->assertRedirect(route('auth.login'));
    }
}
