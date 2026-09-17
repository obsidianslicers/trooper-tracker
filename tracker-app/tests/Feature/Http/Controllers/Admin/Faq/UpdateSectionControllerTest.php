<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_section_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();

        $response = $this->actingAs($trooper)->get(
            route('admin.faq.sections.update', ['section' => $section->id])
        );

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page
            ->component('admin/faq/UpdateSection')
            ->where('section.id', $section->id)
        );
    }

    public function test_invoke_requires_authentication(): void
    {
        $section = FaqSection::factory()->create();

        $response = $this->get(route('admin.faq.sections.update', ['section' => $section->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
