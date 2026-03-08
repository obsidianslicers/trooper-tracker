<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTroopersForPickerQuery;
use App\Features\Troopers\Queries\GetTroopersForPickerQueryHandler;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
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
        $filter = new TrooperFilter(new Request());

        $subject = new GetTroopersForPickerQueryHandler();

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

        $subject = new GetTroopersForPickerQueryHandler();

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

        $subject = new GetTroopersForPickerQueryHandler();

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertCount(1, $result);
        $this->assertSame('Alpha Trooper', $result->first()->display_name);
    }

    public function test_invoke_excludes_troopers_without_setup_completed(): void
    {
        Trooper::factory()->asMember()->withSetupIncomplete()->withDisplayName('Incomplete Setup')->create();
        Trooper::factory()->asMember()->withSetupCompleted()->withDisplayName('Complete Setup')->create();

        $trooper = Trooper::factory()->asMember()->create();
        $filter = new TrooperFilter(new Request(['search_term' => 'Setup']));

        $subject = new GetTroopersForPickerQueryHandler();

        $result = $subject(new GetTroopersForPickerQuery($trooper, $filter, []));

        $this->assertCount(1, $result);
        $this->assertSame('Complete Setup', $result->first()->display_name);
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

        $subject = new GetTroopersForPickerQueryHandler();

        $result = $subject(new GetTroopersForPickerQuery($moderator, $filter, [
            'moderated_only' => true,
        ]));

        $this->assertCount(1, $result);
        $this->assertSame('Moderated Trooper', $result->first()->display_name);
    }
}
