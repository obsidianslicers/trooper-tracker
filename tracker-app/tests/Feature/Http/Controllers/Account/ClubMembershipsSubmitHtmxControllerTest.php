<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Http\Controllers\Account\ClubMembershipsSubmitHtmxController;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @see ClubMembershipsSubmitHtmxController
 */
class ClubMembershipsSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $response = $this->post(route('account.club-memberships-htmx'), [
            'organization_id' => $organization->id,
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_submits_join_request_and_returns_row_fragment(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $response = $this->actingAs($trooper)->post(route('account.club-memberships-htmx'), [
            'organization_id' => $organization->id,
        ]);

        $response->assertOk();
        $response->assertViewIs('pages.account.club-memberships-row');
        $response->assertSee('id="club-membership-form"', false);
        $this->assertNotEmpty($response->headers->get('X-Flash-Message'));
        $this->assertDatabaseHas('tt_join_requests', [
            'trooper_id' => $trooper->id,
            'organization_id' => $organization->id,
            'primary_organization_id' => $organization->id,
            'status' => 'pending',
        ]);
        Queue::assertPushed(SendJoinRequestNotificationsJob::class);
    }

    public function test_invoke_validates_organization_id_required(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->actingAs($trooper)->post(route('account.club-memberships-htmx'), []);

        $response->assertSessionHasErrors('organization_id');
    }
}
