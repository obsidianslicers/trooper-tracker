<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Shares\Pages;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowOrganizationShareImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_the_logo_as_a_png(): void
    {
        $organization = Organization::factory()->create([Organization::IMAGE_PATH_LG => null]);

        $response = $this->get(route('shares.og-image', $organization));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }
}
