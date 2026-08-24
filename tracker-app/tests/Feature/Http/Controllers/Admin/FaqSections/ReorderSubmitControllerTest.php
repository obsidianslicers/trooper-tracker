<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\FaqSections;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_sort_order_for_given_ids(): void
    {
        $trooper   = Trooper::factory()->asAdministrator()->create();
        $section_a = FaqSection::factory()->create([FaqSection::SORT_ORDER => 1]);
        $section_b = FaqSection::factory()->create([FaqSection::SORT_ORDER => 2]);
        $section_c = FaqSection::factory()->create([FaqSection::SORT_ORDER => 3]);

        $response = $this->actingAs($trooper)->post(route('admin.faq.sections.reorder'), [
            'ids' => [$section_c->id, $section_a->id, $section_b->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_faq_sections', [FaqSection::ID => $section_c->id, FaqSection::SORT_ORDER => 1]);
        $this->assertDatabaseHas('tt_faq_sections', [FaqSection::ID => $section_a->id, FaqSection::SORT_ORDER => 2]);
        $this->assertDatabaseHas('tt_faq_sections', [FaqSection::ID => $section_b->id, FaqSection::SORT_ORDER => 3]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.faq.sections.reorder'), ['ids' => []]);

        $response->assertUnauthorized();
    }
}
