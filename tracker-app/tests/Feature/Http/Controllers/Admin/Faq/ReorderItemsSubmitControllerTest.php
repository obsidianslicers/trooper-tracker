<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderItemsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_item_sort_order_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $faq_a = Faq::factory()->create([Faq::SORT_ORDER => 1]);
        $faq_b = Faq::factory()->create([Faq::SORT_ORDER => 2]);
        $faq_c = Faq::factory()->create([Faq::SORT_ORDER => 3]);

        $response = $this->actingAs($trooper)->post(route('admin.faq.items.reorder'), [
            'ids' => [$faq_c->id, $faq_a->id, $faq_b->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_faq', [Faq::ID => $faq_c->id, Faq::SORT_ORDER => 1]);
        $this->assertDatabaseHas('tt_faq', [Faq::ID => $faq_a->id, Faq::SORT_ORDER => 2]);
        $this->assertDatabaseHas('tt_faq', [Faq::ID => $faq_b->id, Faq::SORT_ORDER => 3]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.faq.items.reorder'), ['ids' => []]);

        $response->assertUnauthorized();
    }
}
