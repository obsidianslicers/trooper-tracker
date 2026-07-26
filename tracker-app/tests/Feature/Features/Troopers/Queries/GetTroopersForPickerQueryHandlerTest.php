<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Enums\TrooperPickerMode;
use App\Features\Troopers\Queries\GetTroopersForPickerQuery;
use App\Features\Troopers\Queries\GetTroopersForPickerQueryHandler;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperFriend;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetTroopersForPickerQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_empty_collection_when_filter_has_no_criteria(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request);

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertEmpty($result);
    }

    public function test_invoke_filters_by_organization_id_when_provided(): void
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

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, [
            'organization_id' => $org_included->id,
        ]));

        $this->assertCount(1, $result);
        $this->assertSame('Inside Member', $result->first()->display_name);
    }

    public function test_invoke_filters_by_search_term(): void
    {
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Alpha Trooper')->create();
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Zeta Squad')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Alpha']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertCount(1, $result);
        $this->assertSame('Alpha Trooper', $result->first()->display_name);
    }

    public function test_invoke_filters_by_organization_identifier(): void
    {
        $trooper = Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Identifier Match')->create();
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('No Identifier')->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization(Organization::factory()->create())
            ->withIdentifier('TK-421')
            ->create();

        $requesting_trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'TK-421']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($requesting_trooper, $filter, []));

        $this->assertSame(['Identifier Match'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_orders_by_relevance_when_search_term_present(): void
    {
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Anakin Skywalker')->create();
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Skywalker Ranch')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Skywalker']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertSame(
            ['Skywalker Ranch', 'Anakin Skywalker'],
            $result->pluck(Trooper::DISPLAY_NAME)->all()
        );
    }

    public function test_invoke_falls_back_to_loose_search_when_full_search_finds_nothing(): void
    {
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Matthew Drennan')->create();
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Other Trooper')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Matthew Smith']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertSame(['Matthew Drennan'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_excludes_troopers_without_setup_completed(): void
    {
        Trooper::factory()->asMember()->withSetupIncomplete()->withDisplayName('Incomplete Setup')->create();
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Complete Setup')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Setup']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertCount(1, $result);
        $this->assertSame('Complete Setup', $result->first()->display_name);
    }

    public function test_invoke_includes_minor_when_requesting_trooper_is_guardian(): void
    {
        $guardian = Trooper::factory()->asMember()->create();

        Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('Guardian Minor')
            ->withGuardian($guardian)
            ->create();

        $filter = new TrooperFilter(new Request(['search_term' => 'Guardian Minor']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($guardian, $filter, []));

        $this->assertCount(1, $result);
        $this->assertSame('Guardian Minor', $result->first()->display_name);
    }

    public function test_invoke_excludes_minor_when_requesting_trooper_is_not_guardian(): void
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

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($requesting_trooper, $filter, []));

        $this->assertCount(0, $result);
    }

    public function test_invoke_filters_by_moderated_only(): void
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
        $outside_trooper = Trooper::factory()
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

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($moderator, $filter, [
            'moderated_only' => true,
        ]));

        $this->assertCount(1, $result);
        $this->assertSame('Moderated Trooper', $result->first()->display_name);
    }

    public function test_invoke_excludes_inactive_troopers(): void
    {
        Trooper::factory()->asMember()->withDisplayName('Active Trooper')->create();
        Trooper::factory()->asMember()->asRetired()->withDisplayName('Retired Trooper')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Trooper']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertSame(['Active Trooper'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_returns_results_sorted_by_display_name(): void
    {
        Trooper::factory()->asMember()->withDisplayName('Zulu Trooper')->create();
        Trooper::factory()->asMember()->withDisplayName('Alpha Trooper')->create();
        Trooper::factory()->asMember()->withDisplayName('Bravo Trooper')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Trooper']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertSame(
            ['Alpha Trooper', 'Bravo Trooper', 'Zulu Trooper'],
            $result->pluck(Trooper::DISPLAY_NAME)->all()
        );
    }

    public function test_invoke_with_friends_picker_mode_filters_to_friend_ids_for_requesting_trooper(): void
    {
        $requesting_trooper = Trooper::factory()
            ->asMember()
            ->withDisplayName('Requesting Trooper')
            ->create();
        $friend_target = Trooper::factory()->asMember()->withDisplayName('Friend Target')->create();
        Trooper::factory()->asMember()->withDisplayName('Friend Stranger')->create();

        TrooperFriend::factory()
            ->forTrooper($requesting_trooper)
            ->forFriend($friend_target)
            ->create();

        $filter = new TrooperFilter(new Request(['search_term' => 'Friend Target']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($requesting_trooper, $filter, [
            'picker_mode' => TrooperPickerMode::FRIENDS->value,
        ]));

        $this->assertSame(['Friend Target'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_with_friends_picker_mode_returns_results_without_filter_criteria(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->create();
        $friend_alpha = Trooper::factory()->asMember()->withDisplayName('Alpha Friend')->create();
        $friend_bravo = Trooper::factory()->asMember()->withDisplayName('Bravo Friend')->create();
        Trooper::factory()->asMember()->withDisplayName('Non Friend')->create();

        TrooperFriend::factory()->forTrooper($requesting_trooper)->forFriend($friend_alpha)->create();
        TrooperFriend::factory()->forTrooper($requesting_trooper)->forFriend($friend_bravo)->create();

        $filter = new TrooperFilter(new Request);

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($requesting_trooper, $filter, [
            'picker_mode' => TrooperPickerMode::FRIENDS->value,
        ]));

        $this->assertSame(['Alpha Friend', 'Bravo Friend'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_returns_empty_collection_when_friends_picker_mode_has_no_friends(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->create();
        Trooper::factory()->asMember()->withDisplayName('Unrelated Trooper')->create();

        $filter = new TrooperFilter(new Request);

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($requesting_trooper, $filter, [
            'picker_mode' => TrooperPickerMode::FRIENDS->value,
        ]));

        $this->assertEmpty($result);
    }

    public function test_invoke_with_friends_picker_mode_and_goodall_search_term_when_not_local(): void
    {
        $this->assertFalse(app()->isLocal());

        $requesting_trooper = Trooper::factory()
            ->asMember()
            ->withDisplayName('Requesting Trooper')
            ->create();

        $friend_goodall = Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('TK Goodall')
            ->create();

        $friend_without_setup = Trooper::factory()
            ->asMember()
            ->withSetupIncomplete()
            ->withDisplayName('DZ Goodall')
            ->create();

        Trooper::factory()
            ->asMember()
            ->withSetupCompleted()
            ->withDisplayName('AA Goodall')
            ->create();

        $filter = new TrooperFilter(new Request(['search_term' => 'Goodall']));

        $subject = new GetTroopersForPickerQueryHandler;

        $result = $subject(new GetTroopersForPickerQuery($requesting_trooper, $filter, [
            'picker_mode' => TrooperPickerMode::FRIENDS->value,
        ]));

        $this->assertSame(
            ['AA Goodall', 'TK Goodall'],
            $result->pluck(Trooper::DISPLAY_NAME)->all()
        );
    }
}
