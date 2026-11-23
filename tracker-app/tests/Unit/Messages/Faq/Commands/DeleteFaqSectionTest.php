<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands;

use App\Messages\Faq\Commands\DeleteFaqSection;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteFaqSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_soft_deletes_section(): void
    {
        $section = FaqSection::factory()->create();

        $subject = new DeleteFaqSection($section);
        $result = $subject->handle();

        $this->assertSame(0, $result);
        $this->assertSoftDeleted('tt_faq_sections', [FaqSection::ID => $section->id]);
    }

    public function test_handle_keeps_a_section_that_still_has_items(): void
    {
        $section = FaqSection::factory()->create();
        Faq::factory()->count(2)->create([Faq::SECTION_ID => $section->id]);

        $subject = new DeleteFaqSection($section);
        $result = $subject->handle();

        $this->assertSame(2, $result);
        $this->assertNotSoftDeleted('tt_faq_sections', [FaqSection::ID => $section->id]);
    }
}
