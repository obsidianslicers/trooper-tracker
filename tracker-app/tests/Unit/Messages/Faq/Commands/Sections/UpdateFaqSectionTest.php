<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands\Sections;

use App\Messages\Faq\Commands\Sections\UpdateFaqSection;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateFaqSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_section_fields(): void
    {
        $section = FaqSection::factory()->create();

        (new UpdateFaqSection(
            section: $section,
            label: 'Updated Label',
            icon: 'fa-solid fa-star',
        ))->handle();

        $this->assertDatabaseHas('tt_faq_sections', [
            FaqSection::ID => $section->id,
            FaqSection::LABEL => 'Updated Label',
            FaqSection::ICON => 'fa-solid fa-star',
        ]);
    }
}
