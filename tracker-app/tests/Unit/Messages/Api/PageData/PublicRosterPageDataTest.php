<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Api\PageData;

use App\Messages\Api\PageData\PublicRosterPageData;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicRosterPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_splits_command_staff_from_members(): void
    {
        $organization = Organization::factory()->create();
        $this->member($organization, 'Staff', moderator: true);
        $member = $this->member($organization, 'Member');
        TrooperOrganization::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->withIdentifier('1234')
            ->create();

        $subject = new PublicRosterPageData(organization_id: $organization->id);
        $result = $subject->handle();

        $this->assertSame(['Staff'], array_column($result['command_staff'], 'name'));
        $this->assertSame([['name' => 'Member', 'identifier' => '1234', 'photo' => null]], $result['members']);
    }

    public function test_handle_keeps_the_roster_for_a_day(): void
    {
        $organization = Organization::factory()->create();

        $subject = new PublicRosterPageData(organization_id: $organization->id);
        $result = $subject->handle();

        $this->assertSame($result, Cache::get(PublicRosterPageData::cacheKey($organization->id)));
    }

    public function test_handle_returns_an_empty_roster_for_an_unknown_club_without_keeping_it(): void
    {
        $subject = new PublicRosterPageData(organization_id: 9999);
        $result = $subject->handle();

        $this->assertSame(['command_staff' => [], 'members' => []], $result);
        $this->assertFalse(Cache::has(PublicRosterPageData::cacheKey(9999)));
    }

    private function member(Organization $organization, string $name, bool $moderator = false): Trooper
    {
        $trooper = Trooper::factory()->asActive()->asMember()->withDisplayName($name)->create();
        $assignment = TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember();

        ($moderator ? $assignment->asModerator() : $assignment)->create();

        return $trooper;
    }
}
