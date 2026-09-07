<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Queries;

use App\Messages\Faq\Queries\GetFaqs;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetFaqsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_sections_and_faqs_in_sort_order(): void
    {
        $last_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 3,
        ]);
        $first_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 1,
        ]);

        $last_faq = Faq::factory()->withSection($first_section)->create([
            Faq::SORT_ORDER => 2,
        ]);
        $first_faq = Faq::factory()->withSection($first_section)->create([
            Faq::SORT_ORDER => 1,
        ]);

        $result = (new GetFaqs())->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(
            [$first_section->id, $last_section->id],
            $result->modelKeys(),
        );
        $this->assertTrue($result->first()->relationLoaded('faqs'));
        $this->assertSame(
            [$first_faq->id, $last_faq->id],
            $result->first()->faqs->modelKeys(),
        );
    }

    public function test_handle_uses_ids_as_tie_breakers_for_sort_order(): void
    {
        $first_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 1,
        ]);
        $second_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 1,
        ]);
        $first_faq = Faq::factory()->withSection($first_section)->create([
            Faq::SORT_ORDER => 1,
        ]);
        $second_faq = Faq::factory()->withSection($first_section)->create([
            Faq::SORT_ORDER => 1,
        ]);

        $result = (new GetFaqs())->handle();

        $this->assertSame(
            [$first_section->id, $second_section->id],
            $result->modelKeys(),
        );
        $this->assertSame(
            [$first_faq->id, $second_faq->id],
            $result->first()->faqs->modelKeys(),
        );
    }

    public function test_handle_returns_empty_collection_when_no_sections_exist(): void
    {
        $result = (new GetFaqs())->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }
}
