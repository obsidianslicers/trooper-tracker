<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_validation_errors_for_invalid_payload(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/create', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post('/admin/events/create', []);

        $response->assertRedirect(route('auth.login'));
    }
}
