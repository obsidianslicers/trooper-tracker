<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_faq_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/faq/create', [
            Faq::SECTION_ID  => $section->id,
            Faq::TITLE       => 'How do I register?',
            Faq::DESCRIPTION => 'Visit the registration page.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_faq', [
            Faq::SECTION_ID => $section->id,
            Faq::TITLE      => 'How do I register?',
            Faq::SORT_ORDER => 1,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $section = FaqSection::factory()->create();

        $response = $this->post('/admin/faq/create', [
            Faq::SECTION_ID => $section->id,
            Faq::TITLE      => 'How do I register?',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
