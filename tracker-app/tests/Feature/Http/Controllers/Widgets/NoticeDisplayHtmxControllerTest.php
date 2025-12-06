<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Widgets;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeDisplayHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_trooper_is_redirected_to_login(): void
    {
        // Act
        $response = $this->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_returns_view_with_zero_count_when_no_notices_exist(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('widgets.notice');
        $response->assertViewHas('count', 0);
        $response->assertViewHas('notice', null);
    }

    public function test_invoke_returns_view_with_one_count_and_message_when_one_notice_exists(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();

        $trooper = Trooper::factory()->withAssignment($unit, member: true)->create();
        $notice = Notice::factory()->active()->withOrganization($unit->parent)->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('widgets.notice');
        $response->assertViewHas('count', 1);
        $response->assertViewHas('notice', function ($message) use ($notice)
        {
            return $message->id === $notice->id;
        });
    }

    public function test_invoke_returns_view_with_correct_count_and_null_message_for_multiple_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        Notice::factory()->count(3)->active()->create(['organization_id' => null]);

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('widgets.notice');
        $response->assertViewHas('count', 3);
        $response->assertViewHas('notice', null);
    }

    public function test_invoke_does_not_count_expired_or_future_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        Notice::factory()->count(2)->future()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('widgets.notice');
        $response->assertViewHas('count', 0);
        $response->assertViewHas('notice', null);
    }
}
