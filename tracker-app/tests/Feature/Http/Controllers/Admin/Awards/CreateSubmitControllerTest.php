<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_award_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/awards/create', [
            'organization_id' => $organization->id,
            'name' => 'Medal of Valor',
            'frequency' => AwardFrequency::ONCE->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_awards', [
            'name' => 'Medal of Valor',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->post('/admin/awards/create', [
            'organization_id' => $organization->id,
            'name' => 'Medal of Valor',
            'frequency' => AwardFrequency::ONCE->value,
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
