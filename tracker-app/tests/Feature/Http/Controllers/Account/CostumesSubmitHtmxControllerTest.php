<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostumesSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_adds_costume_to_trooper_and_returns_htmx_response(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $costume = Costume::factory()->create();

        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'costume_id' => $costume->id,
            ]);

        $response->assertOk();
    }

    public function test_invoke_requires_authentication(): void
    {
        $costume = Costume::factory()->create();

        $response = $this->post(route('account.costumes-htmx'), [
            'costume_id' => $costume->id,
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
