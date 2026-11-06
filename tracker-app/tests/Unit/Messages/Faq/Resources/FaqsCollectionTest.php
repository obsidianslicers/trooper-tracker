<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Resources;

use App\Messages\Faq\Queries\GetFaqs;
use App\Messages\Faq\Resources\FaqsCollection;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FaqsCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_includes_each_section_with_its_questions(): void
    {
        $section = FaqSection::factory()->withLabel('Getting Started')->create();
        $plain = Faq::factory()->withSection($section)->create();
        $video = Faq::factory()->withSection($section)->withVideo()->create();

        $subject = new FaqsCollection(GetFaqs::call());
        $result = $subject->toArray(new Request);

        $this->assertCount(1, $result);
        $this->assertSame($section->id, $result[0][FaqSection::ID]);
        $this->assertSame('Getting Started', $result[0][FaqSection::LABEL]);

        $faqs = collect($result[0]['faqs'])->keyBy(Faq::ID);
        $this->assertFalse($faqs[$plain->id]['has_video']);
        $this->assertTrue($faqs[$video->id]['has_video']);
        $this->assertSame($video->title, $faqs[$video->id][Faq::TITLE]);
    }
}
