<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\PageData;

use App\Messages\Faq\PageData\FaqPageData;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_sections_in_order_with_their_questions_grouped(): void
    {
        $second = FaqSection::factory()->create([FaqSection::SORT_ORDER => 2]);
        $first = FaqSection::factory()->create([FaqSection::SORT_ORDER => 1]);
        $later = Faq::factory()->withSection($first)->create([Faq::SORT_ORDER => 2]);
        $sooner = Faq::factory()->withSection($first)->create([Faq::SORT_ORDER => 1]);
        $other = Faq::factory()->withSection($second)->create();

        $subject = new FaqPageData;
        $result = $subject->handle();

        $this->assertSame([$first->id, $second->id], $result['sections']->pluck(FaqSection::ID)->all());
        $this->assertSame([$sooner->id, $later->id], $result['items'][$first->id]->pluck(Faq::ID)->all());
        $this->assertSame([$other->id], $result['items'][$second->id]->pluck(Faq::ID)->all());
    }
}
