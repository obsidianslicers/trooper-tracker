<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Troopers\Pages;

use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class CostumeApprovalsControllerTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClubs();
    }

    public function test_queue_lists_pending_costumes_in_scope_only(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $in_scope = $this->memberOf($this->garrison);
        $out_of_scope = $this->memberOf($this->other_club);
        $this->costumeFor($in_scope, $this->club);
        $this->costumeFor($out_of_scope, $this->other_club, name: 'Jedi');

        $response = $this->actingAs($moderator)->get(route('admin.troopers.costume-approvals'));

        $response->assertOk();
        $response->assertSee($in_scope->display_name);
        $response->assertSee('Emperor Palpatine');
        $response->assertDontSee($out_of_scope->display_name);
    }

    public function test_moderator_in_scope_approves(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $pending = $this->costumeFor($this->memberOf($this->garrison), $this->club);

        $response = $this->actingAs($moderator)
            ->from(route('admin.troopers.costume-approvals'))
            ->post(route('admin.troopers.costumes.approve', ['trooper_costume' => $pending->id]));

        $response->assertRedirect(route('admin.troopers.costume-approvals'));
        $this->assertFalse(TrooperCostume::find($pending->id)->isPending());
    }

    public function test_moderator_out_of_scope_is_forbidden(): void
    {
        $moderator = $this->moderatorOf($this->other_club);
        $pending = $this->costumeFor($this->memberOf($this->garrison), $this->club);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.costumes.approve', ['trooper_costume' => $pending->id]))
            ->assertForbidden();

        $this->assertTrue(TrooperCostume::withPending()->find($pending->id)->isPending());
    }

    public function test_deny_removes_the_request(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $pending = $this->costumeFor($this->memberOf($this->garrison), $this->club);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.costumes.deny', ['trooper_costume' => $pending->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('tt_trooper_costumes', [TrooperCostume::ID => $pending->id]);
    }

    public function test_revoke_needs_an_approved_costume(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $pending = $this->costumeFor($this->memberOf($this->garrison), $this->club);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.costumes.revoke', ['trooper_costume' => $pending->id]))
            ->assertSessionHasErrors('trooper_costume');

        $this->assertNotSoftDeleted('tt_trooper_costumes', [TrooperCostume::ID => $pending->id]);
    }

    public function test_revoke_takes_back_an_approved_costume(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $approved = $this->costumeFor($this->memberOf($this->garrison), $this->club, pending: false);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.costumes.revoke', ['trooper_costume' => $approved->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('tt_trooper_costumes', [TrooperCostume::ID => $approved->id]);
    }

    public function test_undo_revoke_restores_a_revoked_costume(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $trooper = $this->memberOf($this->garrison);
        $approved = $this->costumeFor($trooper, $this->club, pending: false);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.costumes.revoke', ['trooper_costume' => $approved->id]));

        $this->actingAs($moderator)
            ->get(route('admin.troopers.profile', $trooper))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('costumes.0.status', 'revoked')
                ->where('costumes.0.can_review', true));

        $this->actingAs($moderator)
            ->post(route('admin.troopers.costumes.undo-revoke', ['trooper_costume' => $approved->id]))
            ->assertRedirect();

        $this->assertNotSoftDeleted('tt_trooper_costumes', [TrooperCostume::ID => $approved->id]);
        $this->assertFalse(TrooperCostume::findOrFail($approved->id)->isRevoked());
    }

    public function test_undo_revoke_is_forbidden_out_of_scope(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $approved = $this->costumeFor($this->memberOf($this->garrison), $this->club, pending: false);
        $this->actingAs($moderator)
            ->post(route('admin.troopers.costumes.revoke', ['trooper_costume' => $approved->id]));

        $this->actingAs($this->moderatorOf($this->other_club))
            ->post(route('admin.troopers.costumes.undo-revoke', ['trooper_costume' => $approved->id]))
            ->assertForbidden();
    }

    public function test_trooper_costumes_page_shows_review_buttons_in_scope(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $trooper = $this->memberOf($this->garrison);
        $this->costumeFor($trooper, $this->club);

        $response = $this->actingAs($moderator)->get(route('admin.troopers.profile', $trooper));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('costumes.0.status', 'pending')
            ->where('costumes.0.can_review', true));
    }

    public function test_dashboard_counts_pending_costumes(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $this->costumeFor($this->memberOf($this->garrison), $this->club);

        $this->actingAs($moderator)
            ->get(route('admin.display'))
            ->assertOk()
            ->assertSee('1 costume awaiting review');
    }
}
