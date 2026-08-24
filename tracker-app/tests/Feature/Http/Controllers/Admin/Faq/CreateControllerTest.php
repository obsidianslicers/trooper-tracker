<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_faq_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('admin/faq/Create'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.faq.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
