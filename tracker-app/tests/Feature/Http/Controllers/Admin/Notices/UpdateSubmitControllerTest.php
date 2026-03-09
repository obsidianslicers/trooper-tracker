<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Enums\NoticeType;
use App\Models\Notice;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_notice_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $notice = Notice::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/notices/' . $notice->id . '/update', [
            'title' => 'Updated Briefing',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
            'message' => 'Updated message for all troopers.',
        ]);

        $response->assertRedirect(route('admin.notices.list'));
        $this->assertDatabaseHas('tt_notices', [
            'id' => $notice->id,
            'title' => 'Updated Briefing',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $notice = Notice::factory()->create();

        $response = $this->post('/admin/notices/' . $notice->id . '/update', [
            'title' => 'Updated Briefing',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
            'message' => 'Updated message for all troopers.',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
