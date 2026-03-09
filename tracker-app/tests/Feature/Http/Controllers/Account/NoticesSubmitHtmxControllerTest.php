<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticesSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_marks_notice_as_read_and_returns_htmx_response(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $notice = Notice::factory()
            ->forOrganization($organization)
            ->create();

        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', ['notice' => $notice->id]));

        $response->assertOk();
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();
        $notice = Notice::factory()->forOrganization($organization)->create();

        $response = $this->post(route('account.notices-htmx', ['notice' => $notice->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
