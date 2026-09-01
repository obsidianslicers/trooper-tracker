<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Queries;

use App\Messages\Faq\Queries\GetFaqItems;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetFaqItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_paginated_result_when_no_section_id(): void
    {
        $section = FaqSection::factory()->create();

        Faq::factory()->withSection($section)->count(25)->create();

        $subject = new GetFaqItems();

        $result = $subject->handle();

        $this->assertCount(20, $result);
        $this->assertTrue(method_exists($result, 'total'));
        $this->assertSame(25, $result->total());
    }

    public function test_handle_respects_custom_page_size(): void
    {
        $section = FaqSection::factory()->create();

        Faq::factory()->withSection($section)->count(15)->create();

        $subject = new GetFaqItems(page_size: 10);

        $result = $subject->handle();

        $this->assertCount(10, $result);
        $this->assertTrue(method_exists($result, 'total'));
        $this->assertSame(15, $result->total());
    }

    public function test_handle_filters_by_section_id_and_returns_collection(): void
    {
        $section_included = FaqSection::factory()->create();
        $section_excluded = FaqSection::factory()->create();

        Faq::factory()->withSection($section_included)->count(3)->create();
        Faq::factory()->withSection($section_excluded)->count(2)->create();

        $subject = new GetFaqItems(section_id: $section_included->id);

        $result = $subject->handle();

        $this->assertCount(3, $result);
        $result->each(fn(Faq $faq) => $this->assertSame(
            $section_included->id,
            $faq->{Faq::SECTION_ID}
        ));
    }

    public function test_handle_returns_empty_collection_when_section_has_no_items(): void
    {
        $section = FaqSection::factory()->create();

        $subject = new GetFaqItems(section_id: $section->id);

        $result = $subject->handle();

        $this->assertEmpty($result);
    }

    public function test_handle_orders_by_sort_order_then_id(): void
    {
        $section = FaqSection::factory()->create();

        $second = Faq::factory()
            ->withSection($section)
            ->create([Faq::SORT_ORDER => 2])
        ;
        $first = Faq::factory()
            ->withSection($section)
            ->create([Faq::SORT_ORDER => 1])
        ;
        $third = Faq::factory()
            ->withSection($section)
            ->create([Faq::SORT_ORDER => 2])
        ;

        $subject = new GetFaqItems(section_id: $section->id);

        $result = $subject->handle();

        $this->assertSame($first->id, $result[0]->id);
        $this->assertSame($second->id, $result[1]->id);
        $this->assertSame($third->id, $result[2]->id);
    }

    public function test_handle_eager_loads_faq_section_relationship(): void
    {
        $section = FaqSection::factory()->create();

        Faq::factory()->withSection($section)->create();

        $subject = new GetFaqItems(section_id: $section->id);

        $result = $subject->handle();

        $this->assertTrue($result->first()->relationLoaded('faq_section'));
    }
}
