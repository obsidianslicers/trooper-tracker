<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\FaqSections;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_sections_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        FaqSection::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.sections.list'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.faq.sections.list');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.faq.sections.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
