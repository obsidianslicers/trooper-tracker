<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AddTrooperRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $response = $this->post(route('account.add-trooper-request'), [
            'organization_id' => $organization->id,
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_creates_trooper_request_and_renders_account_index(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::IDENTIFIER_VALIDATION => 'regex:/^TK-[0-9]{4}$/',
        ]);

        $response = $this->actingAs($trooper)->post(route('account.add-trooper-request'), [
            'organization_id' => $organization->id,
            'identifier' => 'TK-1234',
        ]);

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('account/Index')
            ->has('results.organization_requests', 1)
            ->where('results.organization_requests.0.identifier', 'TK-1234')
            ->where('results.organization_requests.0.status', 'pending')
        );

        $this->assertDatabaseHas('tt_trooper_requests', [
            'trooper_id' => $trooper->id,
            'organization_id' => $organization->id,
            'primary_organization_id' => $organization->id,
            'identifier' => 'TK-1234',
            'status' => 'pending',
        ]);
    }
}
