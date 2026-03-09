<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignTrooperSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_assigns_award_to_trooper_and_redirects(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $recipient = Trooper::factory()->asMember()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/awards/' . $award->id . '/assign-trooper', [
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

        $response = $this->post('/admin/awards/' . $award->id . '/assign-trooper', [
            'trooper_id' => $recipient->id,
            'award_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
