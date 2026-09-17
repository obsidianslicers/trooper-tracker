<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSectionSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_faq_section_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();

        $response = $this->actingAs($trooper)->post(
            route('admin.faq.sections.update', ['section' => $section->id]),
            [
                FaqSection::LABEL => 'Updated registration',
                FaqSection::ICON => 'circle-check',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_faq_sections', [
            FaqSection::ID => $section->id,
            FaqSection::LABEL => 'Updated registration',
            FaqSection::ICON => 'circle-check',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $section = FaqSection::factory()->create();

        $response = $this->post(route('admin.faq.sections.update', ['section' => $section->id]), [
            FaqSection::LABEL => 'Updated registration',
            FaqSection::ICON => 'circle-check',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
