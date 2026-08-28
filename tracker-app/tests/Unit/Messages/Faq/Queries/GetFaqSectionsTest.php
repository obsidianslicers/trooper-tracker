<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Queries;

use App\Messages\Faq\Queries\GetFaqSections;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetFaqSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_sections_ordered_by_sort_order_with_faq_counts(): void
    {
        $later_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 2,
        ]);
        $first_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 1,
        ]);

        Faq::factory()->withSection($first_section)->count(2)->create();
        Faq::factory()->withSection($later_section)->create();

        $subject = new GetFaqSections();

        $result = $subject->handle();

        $this->assertCount(2, $result);
        $this->assertSame($first_section->id, $result->first()->id);
        $this->assertSame(2, $result->first()->faqs_count);
        $this->assertSame($later_section->id, $result->last()->id);
        $this->assertSame(1, $result->last()->faqs_count);
    }
}
