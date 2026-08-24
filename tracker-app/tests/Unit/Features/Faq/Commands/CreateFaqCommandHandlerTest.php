<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Faq\Commands;

use App\Features\Faq\Commands\CreateFaqCommand;
use App\Features\Faq\Commands\CreateFaqCommandHandler;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFaqCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private CreateFaqCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CreateFaqCommandHandler;
    }

    public function test_invoke_creates_faq_with_sort_order_one_for_first_item_in_section(): void
    {
        $section = FaqSection::factory()->create();

        $faq = ($this->subject)(new CreateFaqCommand(
            section_id: $section->id,
            title: 'How do I register?',
            description: 'Visit the registration page.',
            video_url: null,
        ));

        $this->assertDatabaseHas('tt_faq', [
            Faq::SECTION_ID => $section->id,
            Faq::TITLE => 'How do I register?',
            Faq::SORT_ORDER => 1,
        ]);
        $this->assertSame($section->id, $faq->section_id);
    }

    public function test_invoke_sets_sort_order_to_max_plus_one(): void
    {
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->create([Faq::SORT_ORDER => 5]);

        $faq = ($this->subject)(new CreateFaqCommand(
            section_id: $section->id,
            title: 'Another question',
            description: null,
            video_url: null,
        ));

        $this->assertSame(6, $faq->sort_order);
    }
}
