<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Events\AwardRevoked;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;

class RemoveTrooperControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_removes_award_from_trooper_and_redirects(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $recipient = Trooper::factory()->asMember()->create();
        $award = Award::factory()->create();

        $award_trooper = AwardTrooper::factory()->create([
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => $recipient->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/awards/'.$award->id.'/troopers', [
            'remove_trooper_id' => $award_trooper->id,
        ]);

        $response->assertRedirect(route('admin.awards.list-troopers', $award));

        $this->assertSoftDeleted('tt_award_troopers', [
            'id' => $award_trooper->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $recipient = Trooper::factory()->asMember()->create();
        $award = Award::factory()->create();

        $award_trooper = AwardTrooper::factory()->create([
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => $recipient->id,
        ]);

        $response = $this->post('/admin/awards/'.$award->id.'/troopers', [
            'remove_trooper_id' => $award_trooper->id,
        ]);

        $response->assertRedirect(route('auth.login'));

        $this->assertDatabaseHas('tt_award_troopers', [
            'id' => $award_trooper->id,
        ]);
    }

    public function test_invoke_fires_award_revoked(): void
    {
        EventFacade::fake([AwardRevoked::class]);
        $admin = Trooper::factory()->asSystemOwner()->create();
        $award = Award::factory()->create();
        $award_trooper = AwardTrooper::factory()->create([
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => Trooper::factory()->asMember()->create()->id,
        ]);

        $this->actingAs($admin)->post('/admin/awards/'.$award->id.'/troopers', [
            'remove_trooper_id' => $award_trooper->id,
        ]);

        EventFacade::assertDispatched(AwardRevoked::class, fn (AwardRevoked $revoked): bool => $revoked->award_trooper->is($award_trooper));
    }
}
