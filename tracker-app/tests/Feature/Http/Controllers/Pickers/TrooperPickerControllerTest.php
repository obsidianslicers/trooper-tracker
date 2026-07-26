<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Pickers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
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

    public function test_invoke_displays_legal_name_and_organization_identifier(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $target = Trooper::factory()
            ->asActive()
            ->withSetupCompleted()
            ->withDisplayName('TK-421')
            ->withLegalName('Matthew Drennan')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($target)
            ->forOrganization(Organization::factory()->create())
            ->withIdentifier('TK-421')
            ->create();

        $response = $this->actingAs($trooper)->get(
            route('pickers.trooper', ['property' => 'trooper_id', 'search_term' => 'Drennan'])
        );

        $response->assertOk();
        $response->assertSee('Matthew Drennan');
        $response->assertSee('TK-421');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('pickers.trooper', ['property' => 'trooper_id']));

        $response->assertRedirect(route('auth.login'));
    }
}
