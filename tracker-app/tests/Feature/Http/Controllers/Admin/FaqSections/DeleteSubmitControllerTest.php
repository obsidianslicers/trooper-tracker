<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\FaqSections;

use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_soft_deletes_empty_section_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();

        $response = $this->actingAs($trooper)->post(route('admin.faq.sections.delete', ['section' => $section->id]));

        $response->assertRedirect(route('admin.faq.sections.list'));
        $this->assertSoftDeleted('tt_faq_sections', [FaqSection::ID => $section->id]);
    }

    public function test_invoke_prevents_deleting_section_with_faqs(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->count(2)->create();

        $response = $this->actingAs($trooper)->post(route('admin.faq.sections.delete', ['section' => $section->id]));

        $response->assertRedirect(route('admin.faq.sections.list'));
        $this->assertDatabaseHas('tt_faq_sections', [FaqSection::ID => $section->id]);
        $this->assertNotSoftDeleted('tt_faq_sections', [FaqSection::ID => $section->id]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $section = FaqSection::factory()->create();

        $response = $this->post(route('admin.faq.sections.delete', ['section' => $section->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
