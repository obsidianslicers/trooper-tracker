<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands;

use App\Messages\Faq\Commands\CreateFaqSection;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFaqSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_section_with_sort_order_one_when_none_exist(): void
    {
        $section = (new CreateFaqSection(
            label: 'Getting Started',
            icon: 'fa-solid fa-rocket',
        ))->handle();

        $this->assertDatabaseHas('tt_faq_sections', [
            FaqSection::LABEL => 'Getting Started',
            FaqSection::SORT_ORDER => 1,
        ]);
        $this->assertSame('Getting Started', $section->label);
    }

    public function test_handle_sets_sort_order_to_max_plus_one(): void
    {
        FaqSection::factory()->create([FaqSection::SORT_ORDER => 5]);

        $section = (new CreateFaqSection(
            label: 'Another Section',
            icon: 'fa-solid fa-star',
        ))->handle();

        $this->assertSame(6, $section->sort_order);
    }
}
