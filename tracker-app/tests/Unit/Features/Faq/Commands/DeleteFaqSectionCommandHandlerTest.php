<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Faq\Commands;

use App\Features\Faq\Commands\DeleteFaqSectionCommand;
use App\Features\Faq\Commands\DeleteFaqSectionCommandHandler;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteFaqSectionCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private DeleteFaqSectionCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DeleteFaqSectionCommandHandler;
    }

    public function test_invoke_soft_deletes_section(): void
    {
        $section = FaqSection::factory()->create();

        ($this->subject)(new DeleteFaqSectionCommand($section));

        $this->assertSoftDeleted('tt_faq_sections', [FaqSection::ID => $section->id]);
    }
}
