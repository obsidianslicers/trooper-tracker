<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteSectionSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_soft_deletes_empty_section_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();

        $response = $this->actingAs($trooper)->post(
            route('admin.faq.sections.delete', ['section' => $section->id])
        );

        $response->assertOk();
        $response->assertInertia(fn($page) => $page->component('admin/faq/ListSections'));
        $this->assertSoftDeleted('tt_faq_sections', [FaqSection::ID => $section->id]);
    }

    public function test_invoke_refuses_to_delete_section_with_items(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->create();

        $response = $this->actingAs($trooper)->post(
            route('admin.faq.sections.delete', ['section' => $section->id])
        );

        $response->assertSessionHasErrors('section');
        $this->assertDatabaseHas('tt_faq_sections', [FaqSection::ID => $section->id]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $section = FaqSection::factory()->create();

        $response = $this->post(route('admin.faq.sections.delete', ['section' => $section->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
