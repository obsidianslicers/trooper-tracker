<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_costume_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post('/admin/costumes/create', [
                'name' => 'Scout Trooper',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_costumes', [
            'name' => 'Scout Trooper',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post('/admin/costumes/create', [
                'name' => 'Scout Trooper',
            ]);

        $response->assertRedirect(route('auth.login'));
    }
}
