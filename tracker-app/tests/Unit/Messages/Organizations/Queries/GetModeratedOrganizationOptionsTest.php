<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Queries;

use App\Messages\Organizations\Queries\GetModeratedOrganizationOptions;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetModeratedOrganizationOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_lists_a_moderators_organizations_indented_by_level(): void
    {
        $club = Organization::factory()->asOrganization()->create(['name' => 'Club', 'sequence' => 1]);
        $garrison = Organization::factory()->asRegion()->withParent($club)->create(['name' => 'Garrison', 'sequence' => 2]);
        Organization::factory()->asOrganization()->create(['name' => 'Other Club']);
        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($club)->asModerator()->create();

        $subject = new GetModeratedOrganizationOptions($moderator, with_everyone: true);
        $result = $subject->handle();

        $this->assertSame([
            ['value' => $club->id, 'label' => 'Club'],
            ['value' => $garrison->id, 'label' => '— Garrison'],
        ], $result);
    }

    public function test_handle_offers_everyone_to_administrators_when_asked(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();

        $with = (new GetModeratedOrganizationOptions($admin, with_everyone: true))->handle();
        $without = (new GetModeratedOrganizationOptions($admin))->handle();

        $this->assertSame(['value' => null, 'label' => 'Everyone'], $with[0]);
        $this->assertNotContains(null, array_column($without, 'value'));
    }
}
