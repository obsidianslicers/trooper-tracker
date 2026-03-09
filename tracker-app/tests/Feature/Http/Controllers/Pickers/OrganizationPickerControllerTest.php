<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Pickers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPickerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_organization_picker_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(
            route('pickers.organization', ['property' => 'organizations'])
        );

        $response->assertOk();
        $response->assertViewIs('pickers.organization');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('pickers.organization', ['property' => 'organizations']));

        $response->assertRedirect(route('auth.login'));
    }
}
