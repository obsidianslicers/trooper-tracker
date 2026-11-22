<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Models\Notice;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_notice_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $notice = Notice::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.notices.update', ['notice' => $notice->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/notices/Update')
            ->where('notice.id', $notice->id)
            ->where('notice.title', $notice->title)
            ->where('notice.starts_at', $notice->starts_at->format('Y-m-d H:i'))
            ->has('notice.trooper_stamps')
            ->missing('organization_options'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $notice = Notice::factory()->create();

        $response = $this->get(route('admin.notices.update', ['notice' => $notice->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
