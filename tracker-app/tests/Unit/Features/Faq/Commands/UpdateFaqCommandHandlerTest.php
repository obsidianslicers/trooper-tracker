<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Faq\Commands;

use App\Features\Faq\Commands\UpdateFaqCommand;
use App\Features\Faq\Commands\UpdateFaqCommandHandler;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateFaqCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private UpdateFaqCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateFaqCommandHandler;
    }

    public function test_invoke_updates_faq_fields(): void
    {
        $section = FaqSection::factory()->create();
        $faq = Faq::factory()->withSection($section)->create();
        $new_section = FaqSection::factory()->create();

        ($this->subject)(new UpdateFaqCommand(
            faq: $faq,
            section_id: $new_section->id,
            title: 'Updated Title',
            description: 'Updated description.',
            video_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ));

        $this->assertDatabaseHas('tt_faq', [
            Faq::ID => $faq->id,
            Faq::SECTION_ID => $new_section->id,
            Faq::TITLE => 'Updated Title',
            Faq::DESCRIPTION => 'Updated description.',
            Faq::VIDEO_URL => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
    }
}
