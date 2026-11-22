<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Queries\GetTroopersForModerator;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetTroopersForModeratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_lists_troopers_created_in_error_for_administrators_only(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($organization)->asModerator()->create();
        $invalid = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::INVALID]);
        $member = Trooper::factory()->create();
        foreach ([$invalid, $member] as $trooper)
        {
            TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->create();
        }

        $for_moderator = $this->idsFor($moderator);
        $for_admin = $this->idsFor(Trooper::factory()->asSystemOwner()->create());

        $this->assertContains($member->id, $for_moderator);
        $this->assertNotContains($invalid->id, $for_moderator);
        $this->assertContains($invalid->id, $for_admin);
    }

    public function test_handle_sorts_by_the_column_asked_for(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->withDisplayName('Mmm')->create();
        Trooper::factory()->withDisplayName('Aaa')->create();
        Trooper::factory()->withDisplayName('Zzz')->create();

        $subject = new GetTroopersForModerator($admin, $this->filter(), Trooper::DISPLAY_NAME, 'desc');
        $names = collect($subject->handle()->items())->pluck(Trooper::DISPLAY_NAME)->all();

        $this->assertSame(['Zzz', 'Mmm', 'Aaa'], $names);
    }

    public function test_handle_tries_any_word_when_every_word_finds_nobody(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $trooper = Trooper::factory()->withLegalName('Jango Fett')->create();

        $subject = new GetTroopersForModerator($admin, $this->filter(['search_term' => 'Jango Skywalker']));

        $this->assertSame([$trooper->id], collect($subject->handle()->items())->pluck('id')->all());
    }

    /** @return array<int, int> */
    private function idsFor(Trooper $moderator): array
    {
        $subject = new GetTroopersForModerator($moderator, $this->filter());

        return collect($subject->handle()->items())->pluck('id')->all();
    }

    /** @param  array<string, string>  $query */
    private function filter(array $query = []): TrooperFilter
    {
        return new TrooperFilter(Request::create('/', 'GET', $query));
    }
}
