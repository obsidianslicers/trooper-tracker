<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Enums\TrooperPickerMode;
use App\Messages\Troopers\Queries\SearchTroopers;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperFriend;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SearchTroopersTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_empty_collection_when_filter_has_no_criteria(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request());

        $subject = new SearchTroopers($trooper, $filter);

        $result = $subject->handle();

        $this->assertEmpty($result);
    }

    public function test_handle_filters_by_organization_id_when_provided(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $org_included = Organization::factory()->withName('Included Org')->create();
        $org_excluded = Organization::factory()->withName('Excluded Org')->create();

        $member_inside = Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('Inside Member')
            ->create();
        $member_outside = Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('Outside Member')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($member_inside)
            ->forOrganization($org_included)
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($member_outside)
            ->forOrganization($org_excluded)
            ->create();

        $filter = new TrooperFilter(new Request(['search_term' => 'Member']));

        $subject = new SearchTroopers($trooper, $filter, $org_included->id);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Inside Member', $result->first()->display_name);
    }

    public function test_handle_filters_by_search_term(): void
    {
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Alpha Trooper')->create();
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Zeta Squad')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Alpha']));

        $subject = new SearchTroopers($trooper, $filter);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Alpha Trooper', $result->first()->display_name);
    }

    public function test_handle_excludes_troopers_without_setup_completed(): void
    {
        Trooper::factory()->asMember()->withSetupIncomplete()->withDisplayName('Incomplete Setup')->create();
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Complete Setup')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Setup']));

        $subject = new SearchTroopers($trooper, $filter);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Complete Setup', $result->first()->display_name);
    }

    public function test_handle_includes_minor_when_requesting_trooper_is_guardian(): void
    {
        $guardian = Trooper::factory()->asMember()->create();

        Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('Guardian Minor')
            ->withGuardian($guardian)
            ->create();

        $filter = new TrooperFilter(new Request(['search_term' => 'Guardian Minor']));

        $subject = new SearchTroopers($guardian, $filter);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Guardian Minor', $result->first()->display_name);
    }

    public function test_handle_excludes_minor_when_requesting_trooper_is_not_guardian(): void
    {
        $guardian = Trooper::factory()->asMember()->create();
        $requesting_trooper = Trooper::factory()->asMember()->create();

        Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('Hidden Minor')
            ->withGuardian($guardian)
            ->create();

        $filter = new TrooperFilter(new Request(['search_term' => 'Hidden Minor']));

        $subject = new SearchTroopers($requesting_trooper, $filter);

        $result = $subject->handle();

        $this->assertCount(0, $result);
    }

    public function test_handle_filters_by_moderated_only(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $moderated_org = Organization::factory()->asOrganization()->withNodePath('100.')->create();

        TrooperAssignment::factory()
            ->forTrooper($moderator)
            ->forOrganization($moderated_org)
            ->asModerator()
            ->create();

        $moderated_trooper = Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('Moderated Trooper')
            ->create();
        Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('Outside Trooper')
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($moderated_trooper)
            ->forOrganization($moderated_org)
            ->asMember()
            ->create();

        $filter = new TrooperFilter(new Request(['search_term' => 'Trooper']));

        $subject = new SearchTroopers($moderator, $filter, null, true);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Moderated Trooper', $result->first()->display_name);
    }

    public function test_handle_returns_results_sorted_by_display_name(): void
    {
        Trooper::factory()->asMember()->withDisplayName('Zulu Trooper')->create();
        Trooper::factory()->asMember()->withDisplayName('Alpha Trooper')->create();
        Trooper::factory()->asMember()->withDisplayName('Bravo Trooper')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Trooper']));

        $subject = new SearchTroopers($trooper, $filter);

        $result = $subject->handle();

        $this->assertSame(
            ['Alpha Trooper', 'Bravo Trooper', 'Zulu Trooper'],
            $result->pluck(Trooper::DISPLAY_NAME)->all(),
        );
    }

    public function test_handle_with_friends_picker_mode_returns_results_without_filter_criteria(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->create();
        $friend_alpha = Trooper::factory()->asMember()->withDisplayName('Alpha Friend')->create();
        $friend_bravo = Trooper::factory()->asMember()->withDisplayName('Bravo Friend')->create();
        Trooper::factory()->asMember()->withDisplayName('Non Friend')->create();

        TrooperFriend::factory()->forTrooper($requesting_trooper)->forFriend($friend_alpha)->create();
        TrooperFriend::factory()->forTrooper($requesting_trooper)->forFriend($friend_bravo)->create();

        $filter = new TrooperFilter(new Request());

        $subject = new SearchTroopers(
            $requesting_trooper,
            $filter,
            null,
            false,
            TrooperPickerMode::FRIENDS,
        );

        $result = $subject->handle();

        $this->assertSame(['Alpha Friend', 'Bravo Friend'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_handle_returns_empty_collection_when_friends_picker_mode_has_no_friends(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->create();
        Trooper::factory()->asMember()->withDisplayName('Unrelated Trooper')->create();

        $filter = new TrooperFilter(new Request());

        $subject = new SearchTroopers(
            $requesting_trooper,
            $filter,
            null,
            false,
            TrooperPickerMode::FRIENDS,
        );

        $result = $subject->handle();

        $this->assertEmpty($result);
    }
}
