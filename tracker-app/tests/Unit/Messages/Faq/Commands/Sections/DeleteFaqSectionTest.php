<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands;

use App\Messages\Faq\Commands\DeleteFaqSection;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteFaqSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_soft_deletes_section(): void
    {
        $section = FaqSection::factory()->create();

        (new DeleteFaqSection($section))->handle();

        $this->assertSoftDeleted('tt_faq_sections', [FaqSection::ID => $section->id]);
    }
}
