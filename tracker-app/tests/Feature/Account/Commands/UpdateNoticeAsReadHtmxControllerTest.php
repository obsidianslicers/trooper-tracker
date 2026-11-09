<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Commands;

use App\Models\Notice;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateNoticeAsReadHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_notice_as_read_htmx_controller_calls_command(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->create();

        $response = $this->actingAs($trooper)->post(
            route('account.notices-htmx', $notice),
            [],
            ['HX-Request' => 'true']
        );

        $response->assertOk();
    }

    public function test_update_notice_as_read_htmx_controller_returns_button(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->create();

        $response = $this->actingAs($trooper)->post(
            route('account.notices-htmx', $notice),
            [],
            ['HX-Request' => 'true']
        );

        $response->assertOk();
        $response->assertSee('envelope-open-text');
        $response->assertSee('btn-outline-secondary');
    }
}
