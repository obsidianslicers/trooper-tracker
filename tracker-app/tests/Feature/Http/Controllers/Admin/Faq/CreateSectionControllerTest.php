<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_section_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.sections.create'));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page->component('admin/faq/CreateSection'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.faq.sections.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
