<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\FaqSections;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_section_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.sections.update', ['section' => $section->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.faq.sections.update');
    }

    public function test_invoke_requires_authentication(): void
    {
        $section = FaqSection::factory()->create();

        $response = $this->get(route('admin.faq.sections.update', ['section' => $section->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
