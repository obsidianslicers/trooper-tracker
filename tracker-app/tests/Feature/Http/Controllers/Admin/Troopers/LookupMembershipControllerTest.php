<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LookupMembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_lookup_data_for_a_pending_request(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $trooper_request = TrooperRequest::factory()
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.troopers.approvals.lookup-membership', $trooper_request));

        $response->assertOk();
        $response->assertJson([
            'identifier' => 'TK-12345',
            'existing_trooper_membership' => null,
            'service_name' => null,
            'member' => null,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper_request = TrooperRequest::factory()->create();

        $response = $this->getJson(route('admin.troopers.approvals.lookup-membership', $trooper_request));

        $response->assertUnauthorized();
    }
}
