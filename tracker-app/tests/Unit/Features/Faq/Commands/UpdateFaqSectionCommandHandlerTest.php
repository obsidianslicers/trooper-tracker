<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Faq\Commands;

use App\Features\Faq\Commands\UpdateFaqSectionCommand;
use App\Features\Faq\Commands\UpdateFaqSectionCommandHandler;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateFaqSectionCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private UpdateFaqSectionCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateFaqSectionCommandHandler;
    }

    public function test_invoke_updates_section_fields(): void
    {
        $section = FaqSection::factory()->create();

        ($this->subject)(new UpdateFaqSectionCommand(
            section: $section,
            label: 'Updated Label',
            icon: 'fa-solid fa-star',
        ));

        $this->assertDatabaseHas('tt_faq_sections', [
            FaqSection::ID => $section->id,
            FaqSection::LABEL => 'Updated Label',
            FaqSection::ICON => 'fa-solid fa-star',
        ]);
    }
}
