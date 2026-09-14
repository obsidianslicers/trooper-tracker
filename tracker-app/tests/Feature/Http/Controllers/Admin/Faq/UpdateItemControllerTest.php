<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateItemControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_item_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();
        $faq = Faq::factory()->withSection($section)->create();

        $response = $this->actingAs($trooper)->get(
            route('admin.faq.items.update', ['item' => $faq->id])
        );

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('admin/faq/UpdateItem')
            ->where('item.id', $faq->id)
            ->has('section_options')
        );
    }

    public function test_invoke_requires_authentication(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->get(route('admin.faq.items.update', ['item' => $faq->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
