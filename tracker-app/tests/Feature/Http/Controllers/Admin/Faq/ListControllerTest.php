<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_faq_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        Faq::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.index'));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('admin/faq/List')
            ->where('sortable', false)
        );
    }

    public function test_invoke_is_sortable_and_unpaginated_when_filtered_by_section(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.index', ['section_id' => $section->id]));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('admin/faq/List')
            ->where('sortable', true)
            ->where('section_id', $section->id)
        );
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.faq.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
