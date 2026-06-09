<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinorsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_minors_view(): void
    {
        $trooper = Trooper::factory()->create();

        $response = $this->actingAs($trooper)->get(route('account.minors'));

        $response->assertOk();
        $response->assertViewIs('pages.account.minors');
    }

    public function test_invoke_passes_minors_to_view(): void
    {
        $guardian = Trooper::factory()->create();
        Trooper::factory()->count(2)->withGuardian($guardian)->create();

        $response = $this->actingAs($guardian)->get(route('account.minors'));

        $response->assertViewHas('minors', fn ($minors) => $minors->count() === 2);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.minors'));

        $response->assertRedirect(route('auth.login'));
    }
}
