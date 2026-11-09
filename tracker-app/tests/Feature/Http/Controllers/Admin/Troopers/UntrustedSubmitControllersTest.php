<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class UntrustedSubmitControllersTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClubs();
    }

    public function test_admin_flags_trooper_and_sends_self_added_costumes_back_to_review(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $trooper = $this->memberOf($this->garrison);
        $self_added = $this->costumeFor($trooper, $this->club, pending: false);
        $synced = $this->costumeFor($trooper, $this->club, pending: false, name: 'Stormtrooper');
        $synced->synchronized_at = now();
        $synced->save();

        $this->actingAs($admin)
            ->post(route('admin.troopers.untrusted', compact('trooper')), [
                'untrusted_reason' => 'Claimed 501st credit for Palpatine',
                'review_costumes' => '1',
            ])
            ->assertRedirect(route('admin.troopers.profile', compact('trooper')));

        $trooper->refresh();
        $this->assertTrue($trooper->is_untrusted);
        $this->assertSame('Claimed 501st credit for Palpatine', $trooper->untrusted_reason);
        $this->assertSame($admin->id, $trooper->untrusted_id);
        $this->assertTrue(TrooperCostume::withPending()->find($self_added->id)->isPending());
        $this->assertFalse(TrooperCostume::find($synced->id)->isPending());
    }

    public function test_reason_is_required(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $trooper = $this->memberOf($this->garrison);

        $this->actingAs($admin)
            ->post(route('admin.troopers.untrusted', compact('trooper')), [])
            ->assertSessionHasErrors('untrusted_reason');

        $this->assertFalse($trooper->fresh()->is_untrusted);
    }

    public function test_moderators_cannot_flag(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $trooper = $this->memberOf($this->garrison);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.untrusted', compact('trooper')), ['untrusted_reason' => 'x'])
            ->assertForbidden();

        $this->assertFalse($trooper->fresh()->is_untrusted);
    }

    public function test_admin_clears_the_flag(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $trooper = Trooper::factory()->asUntrusted()->create();

        $this->actingAs($admin)
            ->post(route('admin.troopers.trusted', compact('trooper')))
            ->assertRedirect();

        $trooper->refresh();
        $this->assertFalse($trooper->is_untrusted);
        $this->assertNull($trooper->untrusted_reason);
    }

    public function test_profile_shows_the_flag_to_moderators_and_the_form_to_admins(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $trooper->is_untrusted = true;
        $trooper->untrusted_reason = 'Spite troops';
        $trooper->save();

        $this->actingAs($this->moderatorOf($this->garrison))
            ->get(route('admin.troopers.profile', compact('trooper')))
            ->assertOk()
            ->assertSee('Spite troops')
            ->assertDontSee('Clear do not trust');

        $this->actingAs(Trooper::factory()->asSystemOwner()->create())
            ->get(route('admin.troopers.profile', compact('trooper')))
            ->assertOk()
            ->assertSee('Clear do not trust');
    }
}
