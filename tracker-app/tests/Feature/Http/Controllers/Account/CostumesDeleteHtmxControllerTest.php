<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Costume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostumesDeleteHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_removes_costume_from_trooper_and_returns_htmx_response(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $costume = Costume::factory()->create();

        $trooper_costume = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forCostume($costume)
            ->create();

        $response = $this->actingAs($trooper)
            ->delete(route('account.costumes') . '-htmx', [
                'trooper_costume_id' => $trooper_costume->id,
            ]);

        $response->assertOk();
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $costume = Costume::factory()->create();

        $trooper_costume = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forCostume($costume)
            ->create();

        $this->post(route('auth.logout'));

        $response = $this->get(route('account.costumes'));

        $response->assertRedirect(route('auth.login'));
    }
}
