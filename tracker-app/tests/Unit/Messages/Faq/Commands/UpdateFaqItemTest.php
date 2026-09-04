<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands;

use App\Messages\Faq\Commands\UpdateFaqItem;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateFaqItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_faq_fields(): void
    {
        $section = FaqSection::factory()->create();
        $faq = Faq::factory()->withSection($section)->create();
        $new_section = FaqSection::factory()->create();

        (new UpdateFaqItem(
            faq: $faq,
            section_id: $new_section->id,
            title: 'Updated Title',
            description: 'Updated description.',
            video_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ))->handle();

        $this->assertDatabaseHas('tt_faq', [
            Faq::ID => $faq->id,
            Faq::SECTION_ID => $new_section->id,
            Faq::TITLE => 'Updated Title',
            Faq::DESCRIPTION => 'Updated description.',
            Faq::VIDEO_URL => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
    }
}
