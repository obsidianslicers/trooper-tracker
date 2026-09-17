<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands;

use App\Messages\Faq\Commands\ReorderFaqItems;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderFaqItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_reassigns_sort_order_from_ordered_ids(): void
    {
        $section = FaqSection::factory()->create();
        $first = Faq::factory()->withSection($section)->create([Faq::SORT_ORDER => 1]);
        $second = Faq::factory()->withSection($section)->create([Faq::SORT_ORDER => 2]);

        (new ReorderFaqItems([$second->id, $first->id]))->handle();

        $this->assertDatabaseHas('tt_faq', [Faq::ID => $second->id, Faq::SORT_ORDER => 1]);
        $this->assertDatabaseHas('tt_faq', [Faq::ID => $first->id, Faq::SORT_ORDER => 2]);
    }
}
