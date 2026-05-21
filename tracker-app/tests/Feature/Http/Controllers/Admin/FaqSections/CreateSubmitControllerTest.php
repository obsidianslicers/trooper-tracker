<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\FaqSections;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_section_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->post('/admin/faq/sections/create', [
            FaqSection::LABEL      => 'Getting Started',
            FaqSection::ICON       => 'fa-solid fa-rocket',
            FaqSection::SORT_ORDER => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_faq_sections', [
            FaqSection::LABEL => 'Getting Started',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post('/admin/faq/sections/create', [
            FaqSection::LABEL => 'Getting Started',
            FaqSection::ICON  => 'fa-solid fa-rocket',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
