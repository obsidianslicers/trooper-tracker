<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands;

use App\Messages\Faq\Commands\ReorderFaqSections;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderFaqSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_reassigns_sort_order_from_ordered_ids(): void
    {
        $first = FaqSection::factory()->create([FaqSection::SORT_ORDER => 1]);
        $second = FaqSection::factory()->create([FaqSection::SORT_ORDER => 2]);

        (new ReorderFaqSections([$second->id, $first->id]))->handle();

        $this->assertDatabaseHas('tt_faq_sections', [FaqSection::ID => $second->id, FaqSection::SORT_ORDER => 1]);
        $this->assertDatabaseHas('tt_faq_sections', [FaqSection::ID => $first->id, FaqSection::SORT_ORDER => 2]);
    }
}
