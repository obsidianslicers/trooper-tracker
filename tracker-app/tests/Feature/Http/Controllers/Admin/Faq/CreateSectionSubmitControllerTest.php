<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSectionSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_faq_section_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($trooper)->post(route('admin.faq.sections.create'), [
            FaqSection::LABEL => 'Registration',
            FaqSection::ICON => 'circle-question',
        ]);

        $this->assertDatabaseHas('tt_faq_sections', [
            FaqSection::LABEL => 'Registration',
            FaqSection::ICON => 'circle-question',
            FaqSection::SORT_ORDER => 1,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post(route('admin.faq.sections.create'), [
            FaqSection::LABEL => 'Registration',
            FaqSection::ICON => 'circle-question',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
