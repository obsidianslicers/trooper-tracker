<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Faq\Commands;

use App\Features\Faq\Commands\CreateFaqSectionCommand;
use App\Features\Faq\Commands\CreateFaqSectionCommandHandler;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFaqSectionCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private CreateFaqSectionCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CreateFaqSectionCommandHandler;
    }

    public function test_invoke_creates_section_with_sort_order_one_when_none_exist(): void
    {
        $section = ($this->subject)(new CreateFaqSectionCommand(
            label: 'Getting Started',
            icon: 'fa-solid fa-rocket',
        ));

        $this->assertDatabaseHas('tt_faq_sections', [
            FaqSection::LABEL => 'Getting Started',
            FaqSection::SORT_ORDER => 1,
        ]);
        $this->assertSame('Getting Started', $section->label);
    }

    public function test_invoke_sets_sort_order_to_max_plus_one(): void
    {
        FaqSection::factory()->create([FaqSection::SORT_ORDER => 5]);

        $section = ($this->subject)(new CreateFaqSectionCommand(
            label: 'Another Section',
            icon: 'fa-solid fa-star',
        ));

        $this->assertSame(6, $section->sort_order);
    }
}
