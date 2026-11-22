<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account\Commands;

use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkNoticeAsReadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_marks_the_notice_read_and_goes_back(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $notice = Notice::factory()->forOrganization(Organization::factory()->create())->create();

        $response = $this->actingAs($trooper)
            ->from(route('events.list'))
            ->post(route('account.update-notice-as-read', ['notice' => $notice->id]));

        $response->assertRedirect(route('events.list'));
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $notice = Notice::factory()->forOrganization(Organization::factory()->create())->create();

        $response = $this->post(route('account.update-notice-as-read', ['notice' => $notice->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
