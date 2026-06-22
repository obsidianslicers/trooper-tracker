<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Contracts\MemberLookupInterface;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use App\Services\MemberLookup\MemberLookupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\MemberLookupHtmxController
 */
class MemberLookupHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $member = Trooper::factory()->asMember()->create();
        $trooper_request = TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->get(route('admin.troopers.trooper-requests.member-lookup', $trooper_request));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_requires_moderate_authorization(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $other_member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($other_member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($member)
            ->get(route('admin.troopers.trooper-requests.member-lookup', $trooper_request));

        $response->assertForbidden();
    }

    public function test_invoke_returns_view_with_null_identifier_when_request_has_no_identifier(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create([TrooperRequest::IDENTIFIER => null]);

        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.trooper-requests.member-lookup', $trooper_request));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.partials.member-lookup');
        $response->assertViewHas('identifier', null);
        $response->assertViewHas('duplicate', null);
        $response->assertViewHas('member', null);
    }

    public function test_invoke_returns_member_data_when_lookup_service_finds_member(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-1234')
            ->create();

        $member_data = [
            'identifier'           => 'TK-1234',
            'formatted_identifier' => 'TK 1234',
            'full_name'            => 'John Trooper',
            'status'               => 'Active',
            'standing'             => 'Good',
            'is_approved'          => true,
            'unit_name'            => 'Dune Sea Garrison',
            'profile_url'          => null,
            'thumbnail_url'        => null,
        ];

        $mock_service = Mockery::mock(MemberLookupInterface::class);
        $mock_service->shouldReceive('lookup')->with('TK-1234')->andReturn($member_data);

        $this->mock(MemberLookupResolver::class)
            ->shouldReceive('resolve')
            ->andReturn($mock_service);

        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.trooper-requests.member-lookup', $trooper_request));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.partials.member-lookup');
        $response->assertViewHas('member', $member_data);
        $response->assertViewHas('identifier', 'TK-1234');
        $response->assertViewHas('duplicate', null);
    }

    public function test_invoke_returns_null_member_when_lookup_service_finds_nothing(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('UNKNOWN-999')
            ->create();

        $mock_service = Mockery::mock(MemberLookupInterface::class);
        $mock_service->shouldReceive('lookup')->with('UNKNOWN-999')->andReturn(null);

        $this->mock(MemberLookupResolver::class)
            ->shouldReceive('resolve')
            ->andReturn($mock_service);

        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.trooper-requests.member-lookup', $trooper_request));

        $response->assertOk();
        $response->assertViewHas('member', null);
        $response->assertViewHas('has_lookup_service', true);
    }

    public function test_invoke_sets_has_lookup_service_false_when_org_has_no_service(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('1234')
            ->create();

        $this->mock(MemberLookupResolver::class)
            ->shouldReceive('resolve')
            ->andReturn(null);

        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.trooper-requests.member-lookup', $trooper_request));

        $response->assertOk();
        $response->assertViewHas('has_lookup_service', false);
        $response->assertViewHas('member', null);
    }

    public function test_invoke_flags_duplicate_when_same_identifier_exists_on_another_trooper_request(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $existing_member = Trooper::factory()->asMember()->create();
        $new_member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperRequest::withoutEvents(fn() => TrooperRequest::factory()
            ->forTrooper($existing_member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('DUPE-42')
            ->create());

        $new_request = TrooperRequest::withoutEvents(fn() => TrooperRequest::factory()
            ->forTrooper($new_member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('DUPE-42')
            ->create());

        $this->mock(MemberLookupResolver::class)
            ->shouldReceive('resolve')
            ->andReturn(null);

        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.trooper-requests.member-lookup', $new_request));

        $response->assertOk();
        $response->assertViewHas('identifier', 'DUPE-42');

        $duplicate = $response->viewData('duplicate');
        $this->assertNotNull($duplicate);
        $this->assertSame($existing_member->id, $duplicate->id);
    }

    public function test_invoke_flags_duplicate_when_same_identifier_exists_on_trooper_organization(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $existing_member = Trooper::factory()->asMember()->create();
        $new_member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperOrganization::factory()
            ->forTrooper($existing_member)
            ->forOrganization($organization)
            ->withIdentifier('TK-9999')
            ->create();

        $new_request = TrooperRequest::withoutEvents(fn() => TrooperRequest::factory()
            ->forTrooper($new_member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-9999')
            ->create());

        $this->mock(MemberLookupResolver::class)
            ->shouldReceive('resolve')
            ->andReturn(null);

        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.trooper-requests.member-lookup', $new_request));

        $response->assertOk();

        $duplicate = $response->viewData('duplicate');
        $this->assertNotNull($duplicate);
        $this->assertSame($existing_member->id, $duplicate->id);
    }

    public function test_invoke_does_not_flag_self_as_duplicate(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('SELF-001')
            ->create();

        $this->mock(MemberLookupResolver::class)
            ->shouldReceive('resolve')
            ->andReturn(null);

        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.trooper-requests.member-lookup', $trooper_request));

        $response->assertOk();
        $response->assertViewHas('duplicate', null);
    }
}
