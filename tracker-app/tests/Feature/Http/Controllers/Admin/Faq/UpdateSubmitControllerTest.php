<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_faq_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();
        $faq = Faq::factory()->withSection($section)->create();

        $response = $this->actingAs($trooper)->post("/admin/faq/{$faq->id}/update", [
            Faq::SECTION_ID  => $section->id,
            Faq::TITLE       => 'Updated Title',
            Faq::DESCRIPTION => 'Updated description.',
            Faq::SORT_ORDER  => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_faq', [
            Faq::ID    => $faq->id,
            Faq::TITLE => 'Updated Title',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->post("/admin/faq/{$faq->id}/update", [
            Faq::TITLE => 'Updated Title',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
