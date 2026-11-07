<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Costume;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class TrooperCostumeTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClubs();
    }

    public function test_new_rows_are_approved_unless_created_pending(): void
    {
        $trooper = $this->memberOf($this->garrison);

        $approved = $this->costumeFor($trooper, $this->club, pending: false);
        $pending = $this->costumeFor($trooper, $this->other_club, pending: true, name: 'Jedi');

        $this->assertFalse($approved->refresh()->isPending());
        $this->assertTrue(TrooperCostume::withPending()->find($pending->id)->isPending());
    }

    public function test_pending_rows_are_hidden_from_default_queries_and_relations(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $approved = $this->costumeFor($trooper, $this->club, pending: false, name: 'Stormtrooper');
        $pending = $this->costumeFor($trooper, $this->club);

        $this->assertSame([$approved->id], TrooperCostume::pluck('id')->all());
        $this->assertSame([$approved->id], $trooper->trooper_costumes()->pluck('id')->all());
        $this->assertSame([$pending->id], TrooperCostume::onlyPending()->pluck('id')->all());
        $this->assertCount(2, TrooperCostume::withPending()->get());
    }

    public function test_pending_costume_gives_no_credit_and_cannot_be_picked_for_signup(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $this->memberOf($this->other_club, $trooper);
        $this->costumeFor($trooper, $this->other_club, pending: false);
        $this->costumeFor($trooper, $this->club);

        $costume = Costume::where(Costume::NAME, 'Emperor Palpatine')->first();

        $this->assertSame([$this->other_club->id], $costume->approvedOrgIdsForTrooper($trooper->id));

        $picker = Costume::forTrooper($trooper->id, [$this->club->id])->pluck('id')->all();
        $this->assertNotContains($costume->id, $picker);
    }

    public function test_route_binding_resolves_pending_rows(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $pending = $this->costumeFor($trooper, $this->club);

        $resolved = (new TrooperCostume)->resolveRouteBinding($pending->id);

        $this->assertNotNull($resolved);
        $this->assertSame($pending->id, $resolved->id);
    }
}
