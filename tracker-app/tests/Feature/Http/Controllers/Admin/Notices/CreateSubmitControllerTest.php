<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Enums\NoticeType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_notice_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/notices/create', [
            'organization_id' => $organization->id,
            'title' => 'Operational Briefing',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
            'message' => 'All troopers report to staging area.',
        ]);

        $response->assertRedirect(route('admin.notices.list'));
        $this->assertDatabaseHas('tt_notices', [
            'title' => 'Operational Briefing',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->post('/admin/notices/create', [
            'organization_id' => $organization->id,
            'title' => 'Operational Briefing',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
            'message' => 'All troopers report to staging area.',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
