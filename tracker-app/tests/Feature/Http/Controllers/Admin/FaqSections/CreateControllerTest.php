<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\FaqSections;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_section_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.sections.create'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.faq.sections.create');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.faq.sections.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
