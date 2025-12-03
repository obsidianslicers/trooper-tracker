<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Widgets;

use App\Models\Setting;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportDisplayHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('tracker.forum.name', 'Test Forum');
    }

    public function test_unauthenticated_trooper_is_redirected_to_login(): void
    {
        // Act
        $response = $this->get(route('widgets.support-htmx'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_returns_correct_view_and_data_for_each_day_of_week(): void
    {
        $trooper = Trooper::factory()->create();
        Setting::factory()->create(['key' => 'donate_goal', 'value' => '300']);

        $response = $this->actingAs($trooper)->get(route('widgets.support-htmx'));

        $response->assertOk();
        $response->assertViewIs('widgets.support');
    }
}