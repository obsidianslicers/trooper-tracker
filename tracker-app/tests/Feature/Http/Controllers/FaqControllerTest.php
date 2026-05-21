<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_faq_page(): void
    {
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->count(2)->create();

        $response = $this->get(route('faq'));

        $response->assertOk();
        $response->assertViewIs('pages.faq');
    }

    public function test_invoke_passes_sections_and_items_to_view(): void
    {
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->count(3)->create();

        $response = $this->get(route('faq'));

        $response->assertViewHas('sections');
        $response->assertViewHas('items');
    }
}
