<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Events\AwardGranted;
use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;

class AssignTrooperSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_assigns_award_to_trooper_and_redirects(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $recipient = Trooper::factory()->asMember()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/awards/'.$award->id.'/assign-trooper', [
            'trooper_id' => $recipient->id,
            'award_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('admin.awards.list-troopers', $award));
        $this->assertDatabaseHas('tt_award_troopers', [
            'award_id' => $award->id,
            'trooper_id' => $recipient->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $recipient = Trooper::factory()->asMember()->create();
        $award = Award::factory()->create();

        $response = $this->post('/admin/awards/'.$award->id.'/assign-trooper', [
            'trooper_id' => $recipient->id,
            'award_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_fires_award_granted(): void
    {
        EventFacade::fake([AwardGranted::class]);
        $admin = Trooper::factory()->asSystemOwner()->create();
        $recipient = Trooper::factory()->asMember()->create();
        $award = Award::factory()->create();

        $this->actingAs($admin)->post('/admin/awards/'.$award->id.'/assign-trooper', [
            'trooper_id' => $recipient->id,
            'award_date' => now()->toDateString(),
        ]);

        EventFacade::assertDispatched(AwardGranted::class, fn (AwardGranted $granted): bool => $granted->award_trooper->award_id === $award->id
            && $granted->award_trooper->trooper_id === $recipient->id);
    }
}
