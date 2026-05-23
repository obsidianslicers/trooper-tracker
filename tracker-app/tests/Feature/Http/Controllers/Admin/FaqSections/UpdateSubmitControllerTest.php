<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\FaqSections;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_section_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();

        $response = $this->actingAs($trooper)->post("/admin/faq/sections/{$section->id}/update", [
            FaqSection::LABEL      => 'Updated Label',
            FaqSection::ICON       => 'fa-solid fa-star',
            FaqSection::SORT_ORDER => 10,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_faq_sections', [
            FaqSection::ID    => $section->id,
            FaqSection::LABEL => 'Updated Label',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $section = FaqSection::factory()->create();

        $response = $this->post("/admin/faq/sections/{$section->id}/update", [
            FaqSection::LABEL => 'Updated Label',
            FaqSection::ICON  => 'fa-solid fa-star',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
