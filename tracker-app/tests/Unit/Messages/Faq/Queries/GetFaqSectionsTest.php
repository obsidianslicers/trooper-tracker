<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Queries;

use App\Messages\Faq\Queries\GetFaqSections;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetFaqSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_sections_in_sort_order_and_includes_faq_counts(): void
    {
        $later_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 2,
        ]);
        $first_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 1,
        ]);

        Faq::factory()->withSection($first_section)->count(2)->create();
        Faq::factory()->withSection($later_section)->create();

        $result = (new GetFaqSections())->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame($first_section->id, $result->first()->id);
        $this->assertSame(2, $result->first()->faqs_count);
        $this->assertSame($later_section->id, $result->last()->id);
        $this->assertSame(1, $result->last()->faqs_count);
    }

    public function test_handle_can_skip_faq_counts_when_disabled(): void
    {
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->create();

        $result = (new GetFaqSections(with_faq_count: false))->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertFalse($result->first()->relationLoaded('faqs'));
        $this->assertFalse(isset($result->first()->faqs_count));
    }

    public function test_handle_returns_empty_collection_when_no_sections_exist(): void
    {
        $result = (new GetFaqSections())->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }
}

