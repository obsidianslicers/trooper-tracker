<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Pickers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperPickerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_trooper_picker_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        Trooper::factory()->create();

        $response = $this->actingAs($trooper)->get(
            route('pickers.trooper', ['property' => 'trooper_id', 'search_term' => 'TK'])
        );

        $response->assertOk();
        $response->assertViewIs('pickers.trooper');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('pickers.trooper', ['property' => 'trooper_id']));

        $response->assertRedirect(route('auth.login'));
    }
}
