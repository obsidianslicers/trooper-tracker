<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_costume_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($trooper)->post('/admin/costumes/' . $costume->id . '/update', [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('admin.costumes.list'));
        $this->assertDatabaseHas('tt_costumes', [
            'id' => $costume->id,
            'name' => 'New Name',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $costume = Costume::factory()->create();

        $response = $this->post('/admin/costumes/' . $costume->id . '/update', [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
