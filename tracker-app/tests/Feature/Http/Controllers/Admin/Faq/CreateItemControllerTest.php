<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateItemControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_item_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();

        $response = $this->actingAs($trooper)->get(
            route('admin.faq.items.create', ['section_id' => $section->id])
        );

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('admin/faq/CreateItem')
            ->where('section_id', $section->id)
            ->has('section_options')
        );
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.faq.items.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
