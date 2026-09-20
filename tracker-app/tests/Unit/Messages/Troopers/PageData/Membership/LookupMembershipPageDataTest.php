<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\PageData\Membership;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Messages\Troopers\PageData\Membership\LookupMembershipPageData;
use App\Services\MemberLookup\MemberLookupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LookupMembershipPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_lookup_data_without_existing_membership(): void
    {
        $organization = Organization::factory()->asOrganization()->create([
            Organization::NAME => '501st Legion',
        ]);
        $trooper_request = TrooperRequest::factory()
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();
        $resolver = Mockery::mock(MemberLookupResolver::class);
        $resolver->shouldReceive('resolve')
            ->once()
            ->with($organization)
            ->andReturn(null);

        $subject = new LookupMembershipPageData($resolver, $trooper_request);

        $this->assertSame([
            'identifier' => 'TK-12345',
            'primary_organization' => [
                Organization::ID => $organization->id,
                Organization::NAME => '501st Legion',
            ],
            'existing_trooper_membership' => null,
            'service_name' => null,
            'member' => null,
        ], $subject->handle());
    }

    public function test_handle_returns_existing_membership_and_service_name(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $existing_trooper = Trooper::factory()->asMember()->create([
            Trooper::LEGAL_NAME => 'Existing Trooper',
            Trooper::DISPLAY_NAME => 'Existing',
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
        $trooper_request = TrooperRequest::factory()
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();
        TrooperRequest::factory()
            ->forTrooper($existing_trooper)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();
        $resolver = Mockery::mock(MemberLookupResolver::class);
        $resolver->shouldReceive('resolve')
            ->once()
            ->with($organization)
            ->andReturn(new class
            {});

        $subject = new LookupMembershipPageData($resolver, $trooper_request);

        $result = $subject->handle();

        $this->assertSame('stdClass', $result['service_name']);
        $this->assertSame([
            Trooper::ID => $existing_trooper->id,
            Trooper::LEGAL_NAME => 'Existing Trooper',
            Trooper::DISPLAY_NAME => 'Existing',
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ], $result['existing_trooper_membership']);
    }
}