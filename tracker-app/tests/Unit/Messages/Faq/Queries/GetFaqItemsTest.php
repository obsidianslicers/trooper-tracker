<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Queries;

use App\Messages\Faq\Queries\GetFaqItems;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetFaqItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_all_faq_items_in_sort_order(): void
    {
        $section = FaqSection::factory()->create();

        $low_priority = Faq::factory()
            ->withSection($section)
            ->create([Faq::SORT_ORDER => 2]);

        $high_priority = Faq::factory()
            ->withSection($section)
            ->create([Faq::SORT_ORDER => 1]);

        $result = (new GetFaqItems())->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame($high_priority->id, $result->first()->id);
        $this->assertSame($low_priority->id, $result->last()->id);
        $this->assertSame([1, 2], $result->pluck(Faq::SORT_ORDER)->all());
    }

    public function test_handle_eager_loads_the_faq_section_relationship(): void
    {
        $section = FaqSection::factory()->create();

        Faq::factory()->withSection($section)->create();

        $result = (new GetFaqItems())->handle();

        $this->assertTrue($result->first()->relationLoaded('faq_section'));
        $this->assertInstanceOf(FaqSection::class, $result->first()->faq_section);
        $this->assertSame($section->id, $result->first()->faq_section->id);
    }

    public function test_handle_returns_empty_collection_when_no_faq_items_exist(): void
    {
        $result = (new GetFaqItems())->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }
}
