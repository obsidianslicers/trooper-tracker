<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveTrooperControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_removes_award_from_trooper_and_redirects(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
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
}
