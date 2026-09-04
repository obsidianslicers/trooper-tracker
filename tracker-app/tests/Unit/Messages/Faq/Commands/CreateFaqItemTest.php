<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands;

use App\Messages\Faq\Commands\CreateFaqItem;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFaqItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_faq_with_sort_order_one_for_first_item_in_section(): void
    {
        $section = FaqSection::factory()->create();

        $faq = (new CreateFaqItem(
            section_id: $section->id,
            title: 'How do I register?',
            description: 'Visit the registration page.',
            video_url: null,
        ))->handle();

        $this->assertDatabaseHas('tt_faq', [
            Faq::SECTION_ID => $section->id,
            Faq::TITLE => 'How do I register?',
            Faq::SORT_ORDER => 1,
        ]);
        $this->assertSame($section->id, $faq->section_id);
    }

    public function test_handle_sets_sort_order_to_max_plus_one(): void
    {
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->create([Faq::SORT_ORDER => 5]);

        $faq = (new CreateFaqItem(
            section_id: $section->id,
            title: 'Another question',
            description: null,
            video_url: null,
        ))->handle();

        $this->assertSame(6, $faq->sort_order);
    }
}
